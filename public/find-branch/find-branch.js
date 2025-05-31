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
let userMarker;
let userPosition;

function initMap() {
    const joburg = [-26.2041, 28.0473];
    
    map = L.map('map').setView(joburg, 11);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    // Add campus markers
    Object.entries(campusLocations).forEach(([key, campus]) => {
        addMarker(campus.position, campus.name);
    });

    // Add current location button
    L.control.locate({
        position: 'topleft',
        strings: {
            title: "Show my location"
        },
        // Only show user location when explicitly requested
        flyTo: true,
        keepCurrentZoomLevel: true,
        showPopup: false
    }).addTo(map);

    // Handle campus selection
    document.getElementById('campus').addEventListener('change', function(e) {
        const selectedCampus = campusLocations[e.target.value];
        if (selectedCampus) {
            map.setView(selectedCampus.position, 15);
            showBranchInfo(selectedCampus);
        }
    });

    // Handle form submission
    document.getElementById('branch-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const selectedCampus = campusLocations[document.getElementById('campus').value];
        if (selectedCampus) {
            openGoogleMapsDirections(selectedCampus);
        } else {
            alert('Please select a campus to get directions.');
        }
    });

    // Get user's location only when requested
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            userPosition = [position.coords.latitude, position.coords.longitude];
            // Don't automatically add user marker
            // Only add it when the locate control is clicked
        }, function(error) {
            console.error('Error getting location:', error);
        });
    }
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

function openGoogleMapsDirections(campus) {
    let url = 'https://www.google.com/maps/dir/?api=1';
    
    // Add destination
    url += `&destination=${campus.position[0]},${campus.position[1]}`;
    
    // Add destination name
    url += `&destination_place_id=${encodeURIComponent(campus.name)}`;
    
    // If we have user's location, add it as origin
    if (userPosition) {
        url += `&origin=${userPosition[0]},${userPosition[1]}`;
    }
    
    // Open in new tab
    window.open(url, '_blank');
}

window.onload = initMap;