
document.addEventListener('DOMContentLoaded', function() {
    const deactivateBtn = document.getElementById('deactivateBtn');
    const deleteBtn = document.getElementById('deleteBtn');
    const participantForm = document.getElementById('participantForm');
    const actionTypeInput = document.getElementById('actionType');

    deactivateBtn.addEventListener('click', function(event) {
        event.preventDefault();
        actionTypeInput.value = 'deactivate';
        event.preventDefault();
        participantForm.submit();
    });

    deleteBtn.addEventListener('click', function(event) {
        event.preventDefault();
        actionTypeInput.value = 'delete';
        event.preventDefault();
        participantForm.submit();
    });
});
