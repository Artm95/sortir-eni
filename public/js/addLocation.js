import Location from "./Location.js"
window.onload = ()=>{
    axios.get("/get/locations").then((response)=>{
        new Location(response.data);
    })

}