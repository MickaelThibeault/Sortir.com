
document.addEventListener('DOMContentLoaded', function () {
    const villeSelect = document.getElementById('sortie_lieu_ville_nom');
    const lieuSelect = document.getElementById('sortie_lieu_nom');
    const rueSelect = document.getElementById('sortie_lieu_rue');
    const cpSelect = document.getElementById('sortie_lieu_ville_codePostal');
    const latitudeSelect = document.getElementById('sortie_lieu_latitude');
    const longitudeSelect = document.getElementById('sortie_lieu_longitude');
    const select = document.getElementById('select');
    const text = document.getElementById('text');
    const switchButton = document.querySelectorAll('form svg#switchButton')

    // Objet pour stocker les données des lieux
    let lieuxData = {};


    // Gestionnaire d'événements pour le changement dans le champ de sélection de la ville
    villeSelect.addEventListener('change', function () {
        const villeNom = this.value;

        updateLieux(villeNom);

        // Réinitialiser les champs
        if (!select.dataset.value) {
            lieuSelect.innerHTML = '<option value="">-- Veuillez sélectionner un lieu --</option>';
            rueSelect.value = '';
            latitudeSelect.value = '';
            longitudeSelect.value = '';
        }
    });

    // Gestionnaire d'événements pour le changement dans le champ de sélection du lieu
    lieuSelect.addEventListener('change', function () {
        const lieuNom = this.value;
        // Obtenir les données du lieu sélectionné
        const selectedLieu = lieuxData[lieuNom];

        if (selectedLieu) {
            rueSelect.value = selectedLieu.rue;
            latitudeSelect.value = selectedLieu.latitude;
            longitudeSelect.value = selectedLieu.longitude;
        } else {
            // Réinitialiser les champs si aucun lieu sélectionné
            rueSelect.value = '';
            latitudeSelect.value = '';
            longitudeSelect.value = '';
        }
    });

    villeSelect.dispatchEvent(new Event('change'));

    // Fonction pour mettre à jour les lieux en fonction de la ville sélectionnée
    function updateLieux(villeNom) {
        fetch('/sortie/lieuxByVille/' + villeNom)
            .then(response => response.json())
            .then(data => {
                lieuxData = {};
                if (data.lieux && data.lieux.length > 0) {
                    lieuSelect.innerHTML = '<option value="">-- Veuillez sélectionner un lieu --</option>';
                    cpSelect.value = data.codePostal;
                    data.lieux.forEach(function (lieu) {
                        const option = document.createElement('option');
                        option.value = lieu.nom;
                        option.text = lieu.nom;
                        option.selected = (option.value === select.dataset.value);
                        lieuSelect.add(option);
                        lieuxData[lieu.nom] = {
                            rue: lieu.rue,
                            latitude: lieu.latitude,
                            longitude: lieu.longitude
                        };
                    });
                } else {
                    lieuSelect.innerHTML = '<option value="">-- Aucun lieu enregistré --</option>';
                    cpSelect.value = data.codePostal;
                }
            })
            .catch(error => console.error('Lieux introuvables :', error));
    }

    // Gestionnaire d'événements pour le bouton de basculement (switch button)
    switchButton.forEach( function (button) {
        button.addEventListener('click', function () {

            if (select.style.display === '' || select.style.display === 'block') {
                select.style.display = 'none';
                text.style.display = 'block';
                rueSelect.removeAttribute('readonly');
                rueSelect.classList.remove('bg-light');
                latitudeSelect.removeAttribute('readonly');
                latitudeSelect.classList.remove('bg-light');
                longitudeSelect.removeAttribute('readonly');
                longitudeSelect.classList.remove('bg-light');
            } else {
                select.style.display = 'block';
                text.style.display = 'none';
            }
        });
    })


});

