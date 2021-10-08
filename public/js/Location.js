export default class Location{
    constructor(locationsData) {
        this.submitLocationBtn = document.getElementById("save-location-btn")
        this.citySelect = document.getElementById("event_city");
        this.initialCityId = parseInt(this.citySelect.value)
        this.locationSelect = document.getElementById("event_location")
        this.allLocationsData = locationsData;
        this.renderLocations(this.getLocationsByCity(this.initialCityId));


        this.submitLocationBtn.addEventListener("click", this.submitLocationForm.bind(this))
        this.citySelect.addEventListener("change", async (e)=>{
            let cityId = parseInt(e.target.value)
            this.renderLocations(this.getLocationsByCity(cityId))
        })

        this.locationSelect.addEventListener('change', (e)=>{
            let locationId = parseInt(e.target.value);
            this.setLocationData(this.findLocationById(locationId));
        })
    }

    getLocationsByCity(cityId){
        return this.allLocationsData.filter((location)=>{
            return location.city.id === cityId;
        })
    }

    findLocationById(id){
        return this.allLocationsData.find((location)=>{
            return location.id === id
        })
    }

    addLocation(location){
        this.allLocationsData.push(location)
    }

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
    renderLocations(cityLocations){
        const locationSelect = document.getElementById("event_location")

        let html = "<option >Veuillez choisir un lieu</option>";
        for (let i = 0; i < cityLocations.length; i++) {
            html+= `<option value="${cityLocations[i].id}">${cityLocations[i].name}</option>`
        }

        locationSelect.innerHTML = html

    }
    setLocationData(location){
        const streetField = document.getElementById("location_street_disabled");
        const zipField = document.getElementById("location_zip_disabled");
        const latitudeField = document.getElementById("location_latitude_disabled");
        const longitudeField = document.getElementById("location_longitude_disabled");

        streetField.value = location.street;
        zipField.value = location.city.zipCode;
        latitudeField.value = location.latitude;
        longitudeField.value = location.longitude;
    }

}