// timedate.js
// Modern rewrite of the lab code

let timerID = null;
let timerRunning = false;

/**
 * Display current date (DD/MM/YYYY)
 */
function getTheDate() {
    const today = new Date();
    document.getElementById("date").innerHTML = today.getDate().toString().padStart(2, "0") + "/" +
        (today.getMonth() + 1).toString().padStart(2, "0") + "/" +
        today.getFullYear();
}

/**
 * Stop the clock timer
 */
function stopClock() {
    if (timerRunning) {
        clearTimeout(timerID);
        timerRunning = false;
    }
}

/**
 * Start the clock timer
 */
function startClock() {
    stopClock();
    getTheDate();
    showTime();
}

/**
 * Display current time (HH:MM:SS)
 */
function showTime() {
    const now = new Date();
    let hours = now.getHours();
    const minutes = now.getMinutes();
    const seconds = now.getSeconds();

    document.getElementById("clock").innerHTML = hours.toString().padStart(2, "0") + ":" +
        minutes.toString().padStart(2, "0") + ":" +
        seconds.toString().padStart(2, "0");

    timerID = setTimeout(showTime, 1000);
    timerRunning = true;
}

document.addEventListener("DOMContentLoaded", startClock);