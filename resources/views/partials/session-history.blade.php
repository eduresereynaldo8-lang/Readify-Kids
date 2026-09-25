<script>
    // Recheck server-side authentication when a browser restores a frozen page.
    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            window.location.reload();
        }
    });
</script>