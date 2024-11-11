{block name="content"}
    <div class="panel">
        <h3>{l s='Importer des produits'}</h3>
        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="vitisoft_import_file">{l s=''}</label>
                
                <!-- Conteneur pour les boutons alignés sur la même ligne -->
                <div class="file-upload">
                    <!-- Champ de fichier masqué -->
                    <input type="file" name="vitisoft_import_file" id="vitisoft_import_file" class="custom-file-input" />

                    <!-- Bouton pour choisir un fichier -->
                    <button style="width: 250px;" type="button" class="custom-file-btn" id="choose-file-btn">
                        {l s='Choisir un fichier CSV'}
                    </button>

                    <!-- Bouton pour uploader le fichier -->
                    <button type="submit" name="submit_vitisoft_import" class="btn btn-primary">
                        {l s='Uploader le fichier'}
                    </button>
                </div>
                
                <!-- Affichage du nom du fichier sélectionné -->
                <div id="file-name" class="file-name"></div>
            </div>
        </form>

        {if isset($confirmations) && $confirmations}
            <div class="alert alert-success">
                {foreach from=$confirmations item=confirmation}
                    <p>{$confirmation}</p>
                {/foreach}
            </div>
        {/if}

        {if isset($errors) && $errors}
            <div class="alert alert-danger">
                {foreach from=$errors item=error}
                    <p>{$error}</p>
                {/foreach}
            </div>
        {/if}
    </div>
{/block}


<script>
document.getElementById('choose-file-btn').addEventListener('click', function() {
    document.getElementById('vitisoft_import_file').click();
});

document.getElementById('vitisoft_import_file').addEventListener('change', function() {
    var fileInput = document.getElementById('vitisoft_import_file');
    var fileName = fileInput.files[0] ? fileInput.files[0].name : '';
    var fileNameDisplay = document.getElementById('file-name');

    if (fileName) {
        fileNameDisplay.textContent = '# ' + fileName;  // Afficher le nom du fichier
    } else {
        fileNameDisplay.textContent = '';  // Effacer le nom si aucun fichier n'est sélectionné
    }
});


</script>