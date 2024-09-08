// Sélectionner la modale
const editModal = document.getElementById('editModal');

// écouteur pour l'événement 'show.bs.modal'
editModal.addEventListener('show.bs.modal', function (event) {
    // Bouton qui a déclenché la modale
    const button = event.relatedTarget;

    // Extraire les informations des attributs data-*
    const id = button.getAttribute('data-bs-id');
    const name = button.getAttribute('data-bs-name');
    const codePostal = button.getAttribute('data-bs-cp');

    // Injecter les valeurs dans les champs du formulaire de la modale
    const modalNameInput = editModal.querySelector('#editName');
    const modalCPInput = editModal.querySelector('#editCP');

    modalNameInput.value = name;
    modalCPInput.value = codePostal;
    document.getElementById('editForm').action = `ville/edit/${id}`;

});

