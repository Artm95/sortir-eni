import Location from "./Location.js";
import newLocationForm from './newLocationForm.js';

window.onload = () => {
    axios.get(pathGet).then((response)=>{
        const location = new Location(response.data, 'map');
        new newLocationForm('mapAdd', location)
    })

}