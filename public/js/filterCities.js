const pathname = window.location.pathname.split('/admin')[0];
let citiesData;
const renderCities = (data) => {
    $('#table').html(data.map(city => {
        return `<tr>
                    <td>${city.name}</td>
                    <td>${city.zipCode}</td>
                    <td>
                        <a href="${pathname}/admin/cities/edit/${city.id}" class="btn btn-sm btn-warning">Modifier</a>
                        <button data-path="${pathname}/admin/cities/delete/${city.id}" onclick="setDeletePathOnModal()" data-toggle="modal" data-target="#confirm-modal" class="btn btn-sm btn-danger">Supprimer</button>
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
    axios.get(pathname + '/admin/get/cities').then(res => {
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
