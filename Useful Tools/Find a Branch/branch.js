
const campusLocations = {
    APB: {
        name: 'Auckland Park Bunting Road Campus',
        position: [-26.1855, 28.0157],
        address: '1 Bunting Road, Auckland Park, Johannesburg, 2092'
    },
    APK: {
        name: 'Auckland Park Kingsway Campus',
        position: [-26.1833, 27.9999],
        address: 'Corner Kingsway and University Road, Auckland Park, Johannesburg, 2092'
    },
    SWC: {
        name: 'Soweto Campus',
        position: [-26.2674, 27.8559],
        address: 'Chris Hani Road, Soweto, Johannesburg, 2013'
    },
    DFC: {
        name: 'Doornfontein Campus',
        position: [-26.1984, 28.0413],
        address: '37 Nind Street, Doornfontein, Johannesburg, 2028'
    }
};

let map;
let markers = [];


function initMap() {
    
    const joburg = [-26.2041, 28.0473];
    
    map = L.map('map').setView(joburg, 11);

    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    
    Object.entries(campusLocations).forEach(([key, campus]) => {
        addMarker(campus.position, campus.name);
    });

    
    document.getElementById('campus').addEventListener('change', function(e) {
        const selectedCampus = campusLocations[e.target.value];
        if (selectedCampus) {
            map.setView(selectedCampus.position, 15);
            showBranchInfo(selectedCampus);
        }
    });
}


function addMarker(position, title) {
    const marker = L.marker(position)
        .bindPopup(title)
        .addTo(map);
    markers.push(marker);
}


function showBranchInfo(campus) {
    const infoDiv = document.getElementById('selected-branch-info');
    infoDiv.style.display = 'block';
    infoDiv.innerHTML = `
        <h3>${campus.name}</h3>
        <p>${campus.address}</p>
    `;
}

window.onload = initMap;
