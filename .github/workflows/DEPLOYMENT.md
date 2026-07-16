# GitHub Actions Deployment Workflow Documentation

## Overview

This document describes the GitHub Actions workflow for deploying the Laravel application to Hostinger hosting. The workflow supports three different deployment strategies based on the trigger type.

## Workflow File

**Location:** `.github/workflows/deploy-hostinger.yml`

## Deployment Strategies

### 1. Branch-Based Deployments

#### Production Deployment (master branch)
- **Trigger:** Push to `master` branch or merged PR to `master`
- **Target:** Production environment
- **Path:** `~/domains/{HOSTINGER_PROD_SUBDOMAIN}/public_html/`
- **Composer:** No vendor replacement
- **Migrations:** No automatic migrations

#### Development Deployment (dev branch)
- **Trigger:** Push to `dev` branch or merged PR to `dev`
- **Target:** Development environment
- **Path:** `~/domains/{HOSTINGER_DEV_SUBDOMAIN}/public_html/`
- **Composer:** No vendor replacement
- **Migrations:** No automatic migrations

### 2. Tag-Based Deployments

#### Directory-Specific Deployment (directory tags)
- **Trigger:** Push with directory tags (e.g., `dir:app`, `dir:app,dir:config`)
- **Target:** Specific project environment
- **Path:** `~/{HOSTINGER_CURRENT_PROJECT_PATH}`
- **Composer:** ❌ No vendor replacement (code-only deployment)
- **Migrations:** ❌ No automatic migrations
- **Sync:** Only specified directories are synchronized

## Workflow Steps

### 1. Environment Setup
```yaml
- Checkout code
- Setup PHP 8.2 with required extensions (mbstring, bcmath, intl, pdo_mysql)
- Setup SSH authentication
```

### 2. Deployment Logic

The workflow uses conditional logic to determine the deployment target:

```bash
if [ "${GITHUB_REF##*/}" = "master" ]; then
  # Production deployment
elif [ "${GITHUB_REF##*/}" = "dev" ]; then
  # Development deployment
elif [[ "${GITHUB_REF}" =~ ^refs/tags/ ]]; then
  # Tag-based deployment
fi
```

### 3. File Synchronization

#### Branch Deployments (master/dev)
Use `rsync` with the following exclusions:
- `.git` directory
- `.github` directory
- `.env` file
- `.htaccess` file
- `index.php` file
- Storage logs and cache directories
- Bootstrap cache
- Public storage symlink

#### Tag Deployments (directory-specific)
- **Only syncs specified directories** based on tag format
- **Validates directory existence** before syncing
- **Individual rsync** for each directory
- **No exclusions** (pure directory replacement)

### 4. Post-Deployment

#### Branch Deployments (master/dev)
After file synchronization, the workflow:
1. Makes the deploy script executable
2. Executes the deploy script

#### Tag Deployments (directory-specific)
- **No post-deployment steps** (pure code replacement)
- **No script execution** (only directory sync)

## Required GitHub Secrets

Configure these secrets in your GitHub repository settings:

| Secret Name | Description | Example |
|-------------|-------------|---------|
| `HOSTINGER_SSH_KEY` | Private SSH key for server access | `-----BEGIN OPENSSH PRIVATE KEY-----...` |
| `SSH_PORT` | SSH port number | `22` or `2222` |
| `SSH_USER` | SSH username | `username` |
| `SSH_SERVER` | Server hostname or IP | `server.hostinger.com` |

## Required GitHub Variables

Configure these variables in your GitHub repository settings:

| Variable Name | Description | Example |
|---------------|-------------|---------|
| `HOSTINGER_PROD_SUBDOMAIN` | Production subdomain | `app.yourdomain.com` |
| `HOSTINGER_DEV_SUBDOMAIN` | Development subdomain | `dev.yourdomain.com` |
| `HOSTINGER_CURRENT_PROJECT_PATH` | Specific project path for tag deployments | `domains/spyropharma.com/public_html/crm/` |

## Usage Examples

### Deploy to Production
```bash
git push origin master
```

### Deploy to Development
```bash
git push origin dev
```

### Deploy Specific Directories
```bash
# Deploy only the app directory
git tag dir:app
git push origin dir:app

# Deploy multiple directories
git tag dir:app,dir:config
git push origin dir:app,dir:config

# Deploy app, config, and database directories
git tag dir:app,dir:config,dir:database
git push origin dir:app,dir:config,dir:database
```

## Directory Tag Format

### Tag Naming Convention
- **Format:** `dir:directory1,dir:directory2,dir:directory3`
- **Prefix:** Always use `dir:` before each directory name
- **Separator:** Use commas to separate multiple directories
- **No spaces:** Don't include spaces around commas

### Supported Directories
Common Laravel directories you can deploy:
- `dir:app` - Application code
- `dir:config` - Configuration files
- `dir:database` - Database migrations and seeders
- `dir:resources` - Views, assets, and language files
- `dir:routes` - Route definitions
- `dir:public` - Public assets and index.php
- `dir:storage` - Storage directory (be careful with this)

### Examples
```bash
# Single directory
git tag dir:app
git push origin dir:app

# Multiple directories
git tag dir:app,dir:config
git push origin dir:app,dir:config

# Complex deployment
git tag dir:app,dir:config,dir:database,dir:resources
git push origin dir:app,dir:config,dir:database,dir:resources
```

## Deployment Process Flow

```mermaid
graph TD
    A[Push/PR Trigger] --> B{Check Trigger Type}
    B -->|master| C[Production Deployment]
    B -->|dev| D[Development Deployment]
    B -->|tag| E[Directory Tag Deployment]
    
    C --> F[Sync All Files to Prod Path]
    D --> G[Sync All Files to Dev Path]
    E --> H[Parse Directory Tags]
    
    H --> I[Validate Directories]
    I --> J[Sync Each Directory Individually]
    
    F --> K[Execute Deploy Script]
    G --> K
    J --> L[Directory Sync Complete]
    
    K --> M[Deployment Complete]
    L --> M
```

## Important Notes

### Tag Deployments
- **Use for:** Quick code updates, specific directory changes, or targeted deployments
- **Composer:** No vendor replacement (code-only deployment)
- **Migrations:** No automatic migrations
- **Path:** Uses the specific project path defined in `HOSTINGER_CURRENT_PROJECT_PATH`
- **Sync:** Only specified directories are synchronized

### Branch Deployments
- **Use for:** Regular development and testing
- **Composer:** No vendor replacement (uses existing vendor directory)
- **Migrations:** No automatic migrations
- **Path:** Uses standard subdomain paths

### Security Considerations
- SSH keys are stored as encrypted secrets
- All SSH connections use `StrictHostKeyChecking=no` for automation
- Environment files (`.env`) are excluded from deployment
- Sensitive files are properly excluded from synchronization

## Troubleshooting

### Common Issues

1. **SSH Connection Failed**
   - Verify SSH key is correctly configured
   - Check SSH port and server details
   - Ensure SSH user has proper permissions

2. **Directory Not Found**
   - Check if the directory exists in your repository
   - Verify the tag format is correct (dir:app, dir:config)
   - Ensure directory names match exactly

3. **Tag Format Issues**
   - Use correct format: dir:app,dir:config
   - Separate multiple directories with commas
   - No spaces around commas

4. **File Sync Issues**
   - Check target directory permissions
   - Verify rsync exclusions are correct
   - Ensure sufficient disk space

### Debug Steps

1. Check GitHub Actions logs for detailed error messages
2. Verify all secrets and variables are correctly set
3. Test SSH connection manually
4. Check server-side deploy script execution

## Maintenance

### Updating the Workflow
- Modify `.github/workflows/deploy-hostinger.yml`
- Test changes in development environment first
- Update this documentation when making changes

### Adding New Environments
1. Add new branch/tag conditions
2. Define new paths and variables
3. Update this documentation
4. Test thoroughly before production use

---

**Last Updated:** $(date)
**Workflow Version:** 3.0 (with directory-specific tag deployments)
