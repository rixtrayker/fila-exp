<x-filament-panels::page
    @class([
        'fi-resource-create-record-page',
        'fi-resource-' . str_replace('/', '-', $this->getResource()::getSlug()),
    ])
>
    <x-filament-panels::form
        :wire:key="$this->getId() . '.forms.' . $this->getFormStatePath()"
        wire:submit="create"
    >
        {{ $this->form }}

        <x-filament-panels::form.actions
            :actions="$this->getCachedFormActions()"
            :full-width="$this->hasFullWidthFormActions()"
        />
    </x-filament-panels::form>
</x-filament-panels::page>


<script>
document.addEventListener('livewire:initialized', () => {
    console.log('livewire:initialized');
        if (!navigator.geolocation) {
            alert('Your browser does not support location services. Please enable location services to continue.');
            return;
        }

        navigator.permissions.query({ name: 'geolocation' }).then(function(permissionStatus) {
            if (permissionStatus.state === 'denied') {
                alert('Please enable location services in your browser settings to continue.');
            } else if (permissionStatus.state === 'granted') {
                 navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const latitude = position.coords.latitude;
                        const longitude = position.coords.longitude;

                        // Set the values in the hidden fields
                        Livewire.dispatch('location-fetched', { data: {
                            latitude: latitude,
                            longitude: longitude,
                        }});
                    },
                    (error) => {
                        console.error('Error fetching location:', error);
                    }
                );
            } else if (permissionStatus.state === 'prompt') {
                // Will prompt the user for permission
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const latitude = position.coords.latitude;
                        const longitude = position.coords.longitude;

                        document.querySelector('input[name="latitude"]').value = latitude;
                        document.querySelector('input[name="longitude"]').value = longitude;
                    },
                    (error) => {
                        console.error('Error getting location:', error);
                        alert('Unable to get your location. Please ensure location services are enabled.');
                    }
                );
            }
    });
});
</script>
