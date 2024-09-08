const  editModal = document.getElementById('editModal');
editModal.addEventListener('show.bs.modal', function (event) {
    const  button = event.relatedTarget;

    // Extraire les informations des attributs data-*
    const id = button.getAttribute('data-bs-id');
    const name = button.getAttribute('data-bs-name');

    // Injecter les valeurs dans les champs du formulaire de la modale
    const modalNameInput = editModal.querySelector('#editName');

    modalNameInput.value = name;


    document.getElementById('editForm').action = `campus/edit/${id}`;
});
