const pathname = window.location.pathname.split('/admin')[0];
let citiesData;
const renderCities = (data) => {
    $('#table').html(data.map(city => {
        return `<tr>
                    <td>${city.name}</td>
                    <td>${city.zipCode}</td>
                    <td>
                        <a href="${pathname}/admin/cities/edit/${city.id}" class="btn btn-sm btn-warning">Modifier</a>
                        <a href="${pathname}/admin/cities/delete/${city.id}" class="btn btn-sm btn-danger">Supprimer</a>
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
