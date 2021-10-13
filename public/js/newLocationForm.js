export default class newLocationForm {
    constructor(containerId, suggestionsContainer, dataPath, submitPath, instructionsContainer = null, eventForm = null) {
        this.dataPath = dataPath;
        this.submitPath = submitPath;
        this.initialise(containerId, suggestionsContainer, instructionsContainer, eventForm = null);
    }

    async initialise(containerId, suggestionsContainer, instructionsContainer = null, eventForm = null) {
        this.eventForm = eventForm;
        await axios.get(this.dataPath)
            .then(response => {
                this.cities = response.data
            })
            .catch(error => this.errors = true)
        const mapContainer = document.getElementById(containerId)
        if (this.errors) {
            mapContainer.textContent = 'Une erreur c\'est produite lors de la récupération des villes.';
        } else if (!this.cities.length) {
            mapContainer.textContent = 'Aucune villes trouvée, veuillez contacter un administrateur.';
        } else {
            this.suggestionsContainer = document.getElementById(suggestionsContainer);
            this.citySelect = document.getElementById('location_city');
            this.streetInput = document.getElementById('location_street');
            this.latitudeInput = document.getElementById('location_latitude');
            this.longitudeInput = document.getElementById('location_longitude');
            this.latitudeInput.value = 48.117266;
            this.longitudeInput.value = -1.6777926;
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
            this.latitudeInput.addEventListener('change', (e) => {
                this.map.setView([
                    e.target.value,
                    this.longitudeInput.value
                ]);
                this.marker.setLatLng(L.latLng(e.target.value, this.longitudeInput.value));
            })
            this.longitudeInput.addEventListener('change', (e) => {
                this.map.setView([
                    this.latitudeInput.value,
                    e.target.value
                ]);
                this.marker.setLatLng(L.latLng(this.latitudeInput.value, e.target.value));
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
                        this.suggestions = response.data.features;
                        this.selectedSuggestions = null;
                        this.displaySuggestions();
                    })
                    .catch(error => this.suggestionsContainer.innerHTML = 'Une erreur c\'est produite lors de la récupération des coordonnées.')
            })
            if (instructionsContainer) {
                document.getElementById(instructionsContainer).textContent = 'Vous pouvez aussi sélectionner des coordonnées GPS en déplaçant le marqueur sur la carte ou en remplissant le champ rue et sélectionnant une des suggestions qui apparaitront ci-dessous.';
            }
        }
        this.submitBtn = document.getElementById("save-location-btn")
        this.selectedCity = this.findCityById(parseInt(this.citySelect.value));
        this.submitBtn.addEventListener("click", this.submitForm.bind(this));
    }

    displaySuggestions() {
        this.suggestionsContainer.innerHTML = '';
        this.suggestions.forEach((suggestion, index) => {
            // create html element and add it to the list
            let element = document.createElement('li');
            element.textContent = suggestion.properties.label;
            if (this.selectedSuggestions === index) {
                element.classList = 'text-primary';
            }
            this.suggestionsContainer.appendChild(element);
            // add events
            element.addEventListener('click', () => {
                this.latitudeInput.value = suggestion.geometry.coordinates[1];
                this.longitudeInput.value = suggestion.geometry.coordinates[0];
                this.map.setView([
                    suggestion.geometry.coordinates[1],
                    suggestion.geometry.coordinates[0]
                ], 14);
                this.marker.setLatLng(L.latLng(suggestion.geometry.coordinates[1], suggestion.geometry.coordinates[0]));
                this.selectedSuggestions = index;
                this.displaySuggestions();
            })
        })
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

        axios.post(this.submitPath, formData).then(async(response)=>{
            $('#location-modal').modal('hide')
            if (this.eventForm) {
                await this.eventForm.addLocation(response.data);
                await this.eventForm.renderLocations(this.eventForm.getLocationsByCity(response.data.city.id))
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