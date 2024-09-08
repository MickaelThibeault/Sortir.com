

document.addEventListener('DOMContentLoaded', function () {
        const villeSelect = document.getElementById('sortie_lieu_ville_nom');
        const lieuSelect = document.getElementById('sortie_lieu_nom');
        const rueSelect = document.getElementById('sortie_lieu_rue');
        const cpSelect = document.getElementById('sortie_lieu_ville_codePostal');
        const latitudeSelect = document.getElementById('sortie_lieu_latitude');
        const longitudeSelect = document.getElementById('sortie_lieu_longitude');

        let lieuxData = {};

        villeSelect.addEventListener('change', function () {
            const villeNom = this.value;

            fetch('/sortie/lieuxByVille/' + villeNom)
            .then(response => response.json())
            .then(data => {
                lieuxData = {};
                if (data.lieux && data.lieux.length > 0) {
                    lieuSelect.innerHTML = '<option value="">-- Veuillez sélectionner un lieu --</option>';
                    data.lieux.forEach(function (lieu) {
                        const option = document.createElement('option');
                        option.value = lieu.nom;
                        option.text = lieu.nom;
                        lieuSelect.add(option);
                        lieuxData[lieu.nom] = {
                            rue: lieu.rue,
                            codePostal: lieu.codePostal,
                            latitude: lieu.latitude,
                            longitude: lieu.longitude
                        };
                    });
                } else
                    lieuSelect.innerHTML = '<option value="">-- Aucun lieu enregistré --</option>';
            })
            .catch(error => console.error('Lieux introuvables :', error));
        });

        lieuSelect.addEventListener('change', function () {
            const lieuNom = this.value;
            const selectedLieu = lieuxData[lieuNom];

            if (selectedLieu) {
                rueSelect.value = selectedLieu.rue;
                cpSelect.value = selectedLieu.codePostal;
                latitudeSelect.value = selectedLieu.latitude;
                longitudeSelect.value = selectedLieu.longitude;
            }
        });

        villeSelect.dispatchEvent(new Event('change'));
    });
