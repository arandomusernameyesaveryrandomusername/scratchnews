let footer = document.createElement("footer");
footer.classList.add("footer");

footer.innerHTML = `
<footer>
    &copy; ScratchNews v0.12.1
    &middot; <a href="/about.php">About</a>
    &middot; <a href="/changelog.php">Changelog</a>
    &middot; <a href="/community-guidelines.php">Community Guidelines</a>
    &middot; <a href="/feedback.php">Feedback</a>
</footer>
`;

document.body.appendChild(footer);


