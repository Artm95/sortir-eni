import EventLocation from "./eventLocation.js";
import newLocationForm from './newLocationForm.js';

window.onload = () => {
    const eventLocations = new EventLocation('map', pathLocations);
    new newLocationForm(
        'mapAdd',
        'suggestions-list',
        pathCities,
        pathPostCity,
        'map-instructions',
        eventLocations
    );
}