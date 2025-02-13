<button id="add-gold-btn">Dodaj 200 złota</button>
<script>
    document.getElementById('add-gold-btn').addEventListener('click', async function(e) {
        e.preventDefault();
        try {

            await updateACFFieldsWithGui({
                "bag.gold": 10,
                "bag.papierosy": 10,
                "bag.piwo": -10,

            });
        } catch (error) {
            console.error("Błąd przy dodawaniu złota:", error);
        }
    });
</script>

<!-- Umieść ten kod w szablonie wpisu (post ID 111) -->
<button id="update-relacja-btn" data-post-id="111" data-field="user-<?php echo get_current_user_id(); ?>-usera">
    Dodaj 5 pkt do relacji
</button>
<script>
    document.addEventListener('DOMContentLoaded', function() {

        const btn = document.getElementById('update-relacja-btn');
        btn.addEventListener('click', async function() {
            const postId = this.getAttribute('data-post-id');
            const fieldKey = this.getAttribute('data-field');
            // Przygotowujemy obiekt pól – klucz to nazwa pola, a wartość to delta (w tym przypadku +5)
            const fields = {};
            fields[fieldKey] = 5;

            try {
                const response = await updatePostACFFields(postId, fields);
                showPopup(response.data.message, 'success');
            } catch (error) {
                showPopup(error, 'error');
            }
        });
    });
</script>