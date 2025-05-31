#!/usr/bin/env fish

function concat_php_files_by_directory
    # Check if directory path is provided as an argument
    if test -n "$argv"
        set dir_path $argv[1]
    else
        set dir_path "."
    end

    # Generate output file name
    set base_name (basename $dir_path)
    set output_file "pkg-$base_name.php"

    # Clear output file if it exists
    echo -n "" > $output_file

    # Find all *.php files recursively in the specified directory
    find $dir_path -type f -name "*.php" | while read file
        echo "==================== FILE: $file ====================" >> $output_file
        cat $file >> $output_file
        echo "\n\n" >> $output_file
    end

    echo "PHP files concatenated into $output_file"
end

# Call the function
concat_php_files_by_directory $argv

