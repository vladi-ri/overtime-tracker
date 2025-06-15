window.onload = function() {
    document.querySelector('[name="start_time"]').addEventListener('input', setDefaultBreak);
    document.querySelector('[name="end_time"]').addEventListener('input', setDefaultBreak);
};

document.addEventListener('DOMContentLoaded', function() {
    const startTime    = document.querySelector('[name="start_time"]');
    const endTime      = document.querySelector('[name="end_time"]');
    const breakMinutes = document.querySelector('[name="break_minutes"]');

    setDefaultBreak(startTime, endTime, breakMinutes);
});

/**
 * Sets a default break time based on the duration of the work period.
 * 
 * @param {DomElement} startTime    The start time in HH:MM format.
 * @param {DomElement} endTime      The end time in HH:MM format.
 * @param {DomElement} breakMinutes The current break minutes.
 * 
 * @returns {void}
 */
function setDefaultBreak(startTime, endTime, breakMinutes) {
    const start        = startTime.value;
    const end          = endTime.value;

    if (!start || !end) {
        breakMinutes.value = 0;
        return;
    }

    const [sh, sm]     = start.split(':').map(Number);
    const [eh, em]     = end.split(':').map(Number);

    let minutes        = (eh * 60 + em) - (sh * 60 + sm);

    if (minutes < 0) {
        minutes += 24 * 60;
    }

    const hours        = minutes / 60;
    breakMinutes.value = hours <= 6 ? 15 : 30;

    if (startTime.value && endTime.value && breakMinutes.value) {
        startTime.addEventListener('input', setDefaultBreak);
        endTime.addEventListener('input', setDefaultBreak);
    }
}