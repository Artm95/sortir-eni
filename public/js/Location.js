export default class Location{
    constructor(locationsData) {
        //setting constructor variable
        this.allLocationsData = locationsData;
        //Getting all necessary dom elements
        this.streetField = document.getElementById("location_street_disabled");
        this.zipField = document.getElementById("location_zip_disabled");
        this.latitudeField = document.getElementById("location_latitude_disabled");
        this.longitudeField = document.getElementById("location_longitude_disabled");
        this.submitLocationBtn = document.getElementById("save-location-btn")
        this.citySelect = document.getElementById("event_city");
        this.locationSelect = document.getElementById("event_location")
        //getting initial data
        this.currentLocationId = parseInt(document.getElementById("location-form").dataset.location);
        this.initialCityId = parseInt(this.citySelect.value)
        //if initial data is not null we render current location an city, otherwise all locations by city set in select
        if(this.currentLocationId) this.setLocationData(this.findLocationById(this.currentLocationId))
        else this.renderLocations(this.getLocationsByCity(this.initialCityId));

        //adding event listeners
        //submit listener
        this.submitLocationBtn.addEventListener("click", this.submitLocationForm.bind(this))
        //city select listener
        this.citySelect.addEventListener("change", async (e)=>{
            let cityId = parseInt(e.target.value)
            this.renderLocations(this.getLocationsByCity(cityId))
            this.cleanLocationFields()
        })
        //location select listener
        this.locationSelect.addEventListener('change', (e)=>{
            if (e.target.value){
                let locationId = parseInt(e.target.value);
                this.setLocationData(this.findLocationById(locationId));
            }else{
                this.cleanLocationFields()
            }
        })
    }

    /**
     * Get all locations by city
     * @param cityId
     * @returns array: city's locations
     */
    getLocationsByCity(cityId){
        return this.allLocationsData.filter((location)=>{
            return location.city.id === cityId;
        })
    }

    /**
     * Find location in the list
     * @param id
     * @returns Object: location found in the list
     */
    findLocationById(id){
        return this.allLocationsData.find((location)=>{
            return location.id === id
        })
    }

    /**
     * Add a location to current location's list
     * @param location
     */
    addLocation(location){
        this.allLocationsData.push(location)
    }

    /**
     * Submit locations form to save new location in database
     */
    submitLocationForm(){
        const errorsAlertEl = document.getElementById('location-validation-errors')
        let form = document.forms.location;
        let formData = new FormData(form);

        axios.post('/post/location', formData).then(async(response)=>{
            $('#location-modal').modal('hide')
            await this.addLocation(response.data);
            await this.renderLocations(this.getLocationsByCity(response.data.city.id))
            this.citySelect.value = response.data.city.id
            this.locationSelect.value = response.data.id
            this.setLocationData(response.data)
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
     * Renders location data into input fields
     * @param location
     */
    setLocationData(location){
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