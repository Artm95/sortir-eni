//Getting necesssary dom elements
const searchBtn = document.getElementById("search-btn");
const searchInput = document.getElementById("search-input")

//fetching data
axios.get('/admin/get/campuses').then(response=>{
    let campuses = response.data;
    //rendering all campuses data
    renderCampuses(campuses)
    //adding event listener to search button
    searchBtn.addEventListener("click", ()=>{
        filterCampuses(campuses, searchInput.value)
    })
    //adding event listener to search input
    searchInput.addEventListener('keyup', (e)=>{
        if (e.key ==='Enter') filterCampuses(campuses, searchInput.value)
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
        let editPath = "{{ path('admin_campus_edit', { id: '1' }) }}";
        editPath = editPath.replace('1', campus.id);
        let deletePath = "{{ path('admin_campus_delete', { id: '1' }) }}"
        deletePath =  deletePath.replace('1', campus.id);
        let row = document.createElement('tr');
        row.innerHTML = `<tr>
                    <td>${campus.name}</td>
                    <td>
                        <a href="${editPath}" class="btn btn-sm btn-warning">Modifier</a>
                        <a href="${deletePath}" class="btn btn-sm btn-danger">Supprimer</a>
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
        console.log(campus.name)
        return (campus.name).toLowerCase().includes(searchValue);
    })
    renderCampuses(campusesFiltered)
}