<!-- pages/projects.php -->

<a href="https://github.com/Barcik04" target="_blank" class="git-link1">
    Check out my Github
    <img src="175675/pics/github-mark.png" alt="GitHub" class="git-img1">
</a>
<!-- pages/projects.php -->

<h2 style="text-align:center; margin-top:50px;">My Projects & Related Videos</h2>

<div class="video-gallery">
    <iframe
        src="https://www.youtube.com/embed/HbykCq4f4yE"
        title="YouTube video 1"
        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
        allowfullscreen>
    </iframe>

    <iframe
        src="https://www.youtube.com/embed/2C5URXVCIn8"
        title="YouTube video 2"
        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
        allowfullscreen>
    </iframe>

    <iframe
        src="https://www.youtube.com/embed/N9TdzEIPX0s"
        title="YouTube video 3"
        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
        allowfullscreen>
    </iframe>
</div>

<div id="videoBox" class="video-container">
    <div class="overlay" title="Click to toggle size"></div>
    <iframe
        src="https://www.youtube.com/embed/N9TdzEIPX0s"
        title="YouTube video player"
        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
        allowfullscreen>
    </iframe>
</div>

<script>
    // Vanilla JS instead of jQuery
    document.addEventListener('DOMContentLoaded', function () {
        let enlarged = false;
        const box = document.getElementById('videoBox');
        const overlay = box.querySelector('.overlay');

        overlay.addEventListener('click', function () {
            if (enlarged) {
                box.style.width = '320px';
                box.style.height = '180px';
            } else {
                box.style.width = '960px';
                box.style.height = '540px';
            }
            box.classList.toggle('enlarged');
            enlarged = !enlarged;
        });
    });
</script>
