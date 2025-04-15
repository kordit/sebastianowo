<?php
$npc_data = ['npc_id' => 115];
$conditions = [
    'scena'     => 'kolejowa/main',
    'mission'   => '',
    'instation' => ''
];
$result = get_dialogue($npc_data, null, $conditions);
et_r($result);


?>

<div class="btn" id="open-npc-popup">otworz kurwe</div>


<form id="drug-form">
    <select id="drug-select">
        <option value="szlug">Szlug</option>
        <option value="piwo">Piwo</option>
        <option value="bimber">Bimber</option>
    </select>
    <input type="number" id="drug-amount" min="1" value="1">
    <button type="submit">Wymień</button>
</form>

<script>
    document.getElementById("drug-form").addEventListener("submit", async function(e) {
        e.preventDefault();
        const drug = document.getElementById("drug-select").value;
        const amount = parseInt(document.getElementById("drug-amount").value, 10);
        if (!amount || amount <= 0) {
            showPopup("Podaj liczbę większą od zera!", "error");
            return;
        }
        const mapping = {
            szlug: {
                bagKey: "papierosy",
                life: 10
            },
            piwo: {
                bagKey: "piwo",
                life: 30
            },
            bimber: {
                bagKey: "bimber",
                life: 100
            }
        };
        if (!mapping[drug]) {
            showPopup("Nieprawidłowa używka!", "error");
            return;
        }
        const totalLife = mapping[drug].life * amount;
        const updates = {};
        updates["bag." + mapping[drug].bagKey] = -amount;
        updates["stats.life"] = totalLife;
        try {
            await updateACFFieldsWithGui(updates, ["body"], "Wymiana zakończona.");
            showPopup(error.message || error, "error");
        } catch (error) {
            showPopup(error.message || error, "error");
        }
    });
</script>