let citiesData;
const renderCities = (data) => {
    $('#table').html(data.map(city => {
        let editPathId = editPath.replace('1', city.id);
        let deletePathId =  deletePath.replace('1', city.id);
        return `<tr>
                    <td>${city.name}</td>
                    <td>${city.zipCode}</td>
                    <td>
                        <a href="${editPathId}" class="btn btn-sm btn-warning">Modifier</a>
                        <button data-path="${deletePathId}" onclick="setDeletePathOnModal()" data-toggle="modal" data-target="#confirm-modal" class="btn btn-sm btn-danger">Supprimer</button>
                    </td>
                </tr>`;
    }));
}

const filterCities = (searchString) => {
    searchString = searchString.toLowerCase();
    const citiesFiltered = citiesData.filter( city => {
        return city.name.toLowerCase().includes(searchString);
    })
    renderCities(citiesFiltered);
}

$(document).ready(() => {
    axios.get(pathGetCities).then(res => {
        citiesData = res.data;
        renderCities(res.data);
    })
    $('#search-form').submit(e => {
        e.preventDefault();
        filterCities($('#search').val());
    })
})


/**
 * Set the link to delete campus on "delete" button of a modal
 */
function setDeletePathOnModal(){
    const modalDeleteBtn = document.getElementById("modal-delete-btn");
    let path = event.target.dataset.path;
    modalDeleteBtn.href = path;
}
