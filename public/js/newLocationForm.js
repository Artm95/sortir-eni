export default class newLocationForm {
    constructor(containerId, eventForm = null) {
        this.initialise(containerId, eventForm = null);
    }

    async initialise(containerId, eventForm = null) {
        this.eventForm = eventForm;
        await axios.get(pathCities)
            .then(response => {
                this.cities = response.data
            });
        const mapContainer = document.getElementById(containerId)
        if (this.errors) {
            mapContainer.textContent = 'Une erreur c\'est produite lors de la récupération des villes.';
        } else if (!this.cities.length) {
            mapContainer.textContent = 'Aucune villes trouvée, veuillez contacter un administrateur.';
        } else {
            this.suggestionsContainer = document.getElementById('suggestions-list');
            this.citySelect = document.getElementById('location_city');
            this.streetInput = document.getElementById('location_street');
            this.nameInput = document.getElementById('location_name');
            this.latitudeInput = document.getElementById('location_latitude');
            this.longitudeInput = document.getElementById('location_longitude');
            this.map = L.mapbox.map(containerId)
                .setView([48.117266, -1.6777926], 6)
                .addLayer(L.mapbox.styleLayer('mapbox://styles/mapbox/streets-v11'));
            this.marker = L.marker([48.117266, -1.6777926], {
                icon: L.mapbox.marker.icon({
                    'marker-color': '#f86767'
                }),
                draggable: true
            });
            this.marker.addTo(this.map);
            this.marker.on('dragend', e => {
                const lngLat = this.marker.getLatLng();
                this.latitudeInput.value = lngLat.lat;
                this.longitudeInput.value = lngLat.lng;
            })


            this.citySelect.addEventListener("change", async (e)=>{
                let cityId = parseInt(e.target.value)
                this.selectedCity = this.findCityById(parseInt(cityId));
            })
            this.streetInput.addEventListener("change", async (e)=>{
                axios.get(
                    'https://api-adresse.data.gouv.fr/search',
                    { params: {q: e.target.value+' '+this.selectedCity.name+' '+this.selectedCity.zipCode, type: 'housenumber' }}
                )
                    .then(response => {
                        response.data.features.forEach(location => {
                            let suggestion = document.createElement('li');
                            suggestion.textContent = location.properties.label;
                            suggestion.addEventListener('click', () => {
                                this.latitudeInput.value = location.geometry.coordinates[1];
                                this.longitudeInput.value = location.geometry.coordinates[0];
                                this.map.setView([
                                    location.geometry.coordinates[1],
                                    location.geometry.coordinates[0]
                                ], 14);
                                this.marker.setLatLng(L.latLng(location.geometry.coordinates[1], location.geometry.coordinates[0]));
                            })
                            this.suggestionsContainer.appendChild(suggestion);
                        });
                    })
            })
        }
        //Getting all necessary dom elements
        this.submitLocationBtn = document.getElementById("save-location-btn")
        this.selectedCity = this.findCityById(parseInt(this.citySelect.value));

        this.submitLocationBtn.addEventListener("click", this.submitForm.bind(this));
    }

    /**
     * Find location in the list
     * @param id
     * @returns Object: location found in the list
     */
    findCityById(id){
        return this.cities.find(city => city.id === id)
    }

    /**
     * Submit locations form to save new location in database
     */
    submitForm(){
        const errorsAlertEl = document.getElementById('location-validation-errors')
        let form = document.forms.location;
        let formData = new FormData(form);

        axios.post(pathPost, formData).then(async(response)=>{
            $('#location-modal').modal('hide')
            if (this.eventForm) {
                await this.eventForm.addLocation(response.data);
                await this.eventForm.renderLocations(this.getLocationsByCity(response.data.city.id))
                this.eventForm.citySelect.value = response.data.city.id
                this.eventForm.locationSelect.value = response.data.id
                this.eventForm.setLocationData(response.data)
            }
        }).catch((error)=>{
            errorsAlertEl.innerHTML = "";
            errorsAlertEl.classList.remove('d-none');
            error.response.data.forEach(item=>{
                let element = document.createElement('p')
                element.innerHTML = item
                errorsAlertEl.append(element)
            })
        })

    }
}