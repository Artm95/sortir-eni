//Getting necesssary dom elements
const searchForm = document.getElementById("search-form");
const searchInput = document.getElementById("search-input");
const modalDeleteBtn = document.getElementById("modal-delete-btn");

//fetching data
axios.get(pathGetCampus).then(response=>{
    let campuses = response.data;
    //rendering all campuses data
    renderCampuses(campuses)
    //adding event listener to search button
    searchForm.addEventListener("submit", e => {
        e.preventDefault();
        filterCampuses(campuses, searchInput.value)
    })
})

/**
 * Render table rows with campuses' information
 * @param campuses
 */
function renderCampuses(campuses){
    const tableBody = document.getElementById("campuses-table-body");
    tableBody.innerHTML = ""
    campuses.forEach(campus=>{
        let editPathId = editPath.replace('1', campus.id);
        let deletePathId =  deletePath.replace('1', campus.id);
        let row = document.createElement('tr');
        row.innerHTML = `<tr>
                    <td>${campus.name}</td>
                    <td>
                        <a href="${editPathId}" class="btn btn-sm btn-warning">Modifier</a>
                        <button data-path="${deletePathId}" onclick="setDeletePathOnModal()" data-toggle="modal" data-target="#confirm-modal" class="btn btn-sm btn-danger">Supprimer</button>
                    </td>
                </tr>`
        tableBody.append(row)
    })
}

/**
 * Filter campuses by name and searchValue
 * @param campuses
 * @param searchValue
 */
function filterCampuses(campuses, searchValue){
    searchValue = searchValue.toLowerCase()
    let campusesFiltered = campuses.filter((campus)=>{
        return (campus.name).toLowerCase().includes(searchValue);
    })
    renderCampuses(campusesFiltered)
}

/**
 * Set the link to delete campus on "delete" button of a modal
 */
function setDeletePathOnModal(){
    let path = event.target.dataset.path;
    modalDeleteBtn.href = path;
}