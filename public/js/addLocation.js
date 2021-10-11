import Location from "./Location.js"

window.onload = ()=>{
    //fetching data and initializing an object
    axios.get("/get/locations").then((response)=>{
        new Location(response.data);
    })

}