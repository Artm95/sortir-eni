import Location from "./Location.js";
window.onload = () => {
    axios.get(pathGet).then((response)=>{
        new Location(response.data);
    })

}