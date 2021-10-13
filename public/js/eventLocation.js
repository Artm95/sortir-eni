export default class EventLocation {
    constructor(containerId, dataPath) {
        this.initialise(containerId, dataPath);
    }

    async initialise(containerId, dataPath) {
        await axios.get(dataPath)
            .then(response => this.locations = response.data)
            .catch(error => this.errors = true)
        const mapContainer = document.getElementById(containerId);
        if (this.errors) {
            mapContainer.textContent = 'Une erreur c\'est produite lors de la récupération des lieux.';
        } else if (!this.locations.length) {
            mapContainer.textContent = 'Aucun lieux trouvé, veuillez contacter un administrateur.';
        } else {
            this.map = L.mapbox.map(containerId)
                .setView([48.117266, -1.6777926], 6)
                .addLayer(L.mapbox.styleLayer('mapbox://styles/mapbox/streets-v11'));
            //Getting all necessary dom elements
            this.streetField = document.getElementById("location_street_disabled");
            this.zipField = document.getElementById("location_zip_disabled");
            this.latitudeField = document.getElementById("location_latitude_disabled");
            this.longitudeField = document.getElementById("location_longitude_disabled");
            this.citySelect = document.getElementById("event_city");
            this.locationSelect = document.getElementById("event_location")
            //getting initial data
            this.currentLocationId = parseInt(document.getElementById("location-form").dataset.location);
            this.initialCityId = parseInt(this.citySelect.value)
            //if initial data is not null we render current location an city, otherwise all locations by city set in select
            if (this.currentLocationId) this.setLocationData(this.findLocationById(this.currentLocationId))
            else this.renderLocations(this.getLocationsByCity(this.initialCityId));

            //adding event listeners
            //city select listener
            this.citySelect.addEventListener("change", async (e) => {
                let cityId = parseInt(e.target.value)
                this.renderLocations(this.getLocationsByCity(cityId))
                this.cleanLocationFields()
            })
            //location select listener
            this.locationSelect.addEventListener('change', (e) => {
                if (e.target.value) {
                    let locationId = parseInt(e.target.value);
                    this.setLocationData(this.findLocationById(locationId));
                } else {
                    this.cleanLocationFields()
                }
            })
        }
    }

    /**
     * Get all locations by city
     * @param cityId
     * @returns array: city's locations
     */
    getLocationsByCity(cityId){
        return this.locations.filter((location)=>{
            return location.city.id === cityId;
        })
    }

    /**
     * Find location in the list
     * @param id
     * @returns Object: location found in the list
     */
    findLocationById(id){
        return this.locations.find((location)=>{
            return location.id === id
        })
    }

    /**
     * Add a location to current location's list
     * @param location
     */
    addLocation(location){
        this.locations.push(location)
    }

    /**
     * Render a select with the list of location
     * @param cityLocations: locations to render
     */
    renderLocations(cityLocations){
        const locationSelect = document.getElementById("event_location")

        let html = "<option value=''>Veuillez choisir un lieu</option>";
        for (let i = 0; i < cityLocations.length; i++) {
            html+= `<option value="${cityLocations[i].id}">${cityLocations[i].name}</option>`
        }

        locationSelect.innerHTML = html

    }

    /**
     * Renders location data into input fields and show
     * @param location
     */
    setLocationData(location){
        this.map.setView([location.latitude, location.longitude], 14);
        if (!this.marker) {
            this.marker = L.marker([location.latitude, location.longitude], {
                icon: L.mapbox.marker.icon({
                    'marker-color': '#f86767'
                })
            });
            this.marker.addTo(this.map);
        } else {
            this.marker.setLatLng(L.latLng(location.latitude, location.longitude));
        }
        this.streetField.value = location.street;
        this.zipField.value = location.city.zipCode;
        this.latitudeField.value = location.latitude;
        this.longitudeField.value = location.longitude;
        this.citySelect.value = location.city.id

    }

    /**
     * Clean location fields
     */
    cleanLocationFields(){
        this.streetField.value = "";
        this.zipField.value = "";
        this.latitudeField.value = "";
        this.longitudeField.value = "";
    }

}