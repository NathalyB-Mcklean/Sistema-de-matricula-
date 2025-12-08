<?php
// app/views/estudiante/footer.php
?>
    
    <script>
        document.querySelectorAll('a[href*="logout.php"]').forEach(link => {
            link.addEventListener('click', function(e) {
                if (!confirm('¿Estás seguro de que deseas cerrar sesión?')) {
                    e.preventDefault();
                }
            });
        });
        
        function confirmAction(message, url) {
            if (confirm(message)) {
                window.location.href = url;
            }
        }
    </script>
</body>
</html>