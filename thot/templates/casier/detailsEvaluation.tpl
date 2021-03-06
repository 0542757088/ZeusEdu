<form class="form-vertical" id="evalTravail">

    <div class="row">

        <div class="col-sm-2">
            <img src="{$BASEDIR}photos/{$photo}.jpg" alt="{$matricule}" class="img-responsive">
        </div>

        <div class="col-sm-10">
            <button type="button" class="btn btn-info btn-sm pull-right" id="consignes" title="Consignes" data-idtravail="{$infoTravail.idTravail|default:'-'}"><i class="fa fa-graduation-cap"></i></button>
            <p>
              <strong id="fileName" class="nomFichier">
                  <a href="inc/download.php?type=pTrEl&amp;idTravail={$infoTravail.idTravail}&amp;matricule={$matricule}">
                  {$fileInfos.fileName|default:'-'}
                  </a>
              </strong>
               -> <strong id="fileSize">{$fileInfos.size}</strong><br>
               Remis le: <strong id="dateRemise">{$fileInfos.dateRemise|default:'-'}</strong>
           </p>

           <div class="form-group">
             <label for="remarque">Remarque de l'élève</label>
             <p id="remarque">{$evaluationsTravail.remarque|default:'-'}</p>
           </div>

        </div>

    </div>

        <table class="table table-condensed">
            <thead>
                <tr>
                    <th style="width:70%">Compétences</th>
                    <th style="width:10%">Form/Cert</th>
                    <th style="width:10%">
                        Cote
                        <span
                            class="pull-right smallNotice pop"
                            data-content="Mentions admises: <strong>{$COTEABS}</strong> <br>Toutes ces mentions sont neutres <br><strong>{$COTENULLE}</strong><br>La cote est nulle"
                            data-html="true"
                            data-container="body"
                            data-placement="left">
                        </span>
                    </th>
                    <th style="width:10%">Max</th>
                </tr>
            </thead>
            <tbody>
                {if count($competencesTravail) > 0}
                    {foreach from=$competencesTravail key=idCompetence item=data name=boucle}
                    <tr>
                        <td>{$data.libelle}</td>
                        <td>{if $data.formCert == 'form'}Formatif{else}Certificatif{/if}</td>
                        <td>
                            <input
                                type="text"
                                name="cote_{$idCompetence}"
                                class="form-control input-sm cote"
                                value="{$evaluationsTravail.cotes.$idCompetence.cote|default:''}"
                                tabindex="{$smarty.foreach.boucle.iteration}">
                        </td>
                        <td>
                            <strong>/ {$data.max}</strong>
                            <input type="hidden" name="max_{$idCompetence}" class="maxCompetence" value="{$data.max|default:''}">
                        </td>
                    </tr>
                    {assign var=n value=$smarty.foreach.boucle.iteration}
                    {/foreach}
                {else}
                    <tr>
                        <td colspan="4">
                            <i class="fa fa-warning text-danger"></i> Vous n'avez pas encore indiqué les compétences exercées pour ce travail
                        </td>
                    </tr>
                {/if}
            </tbody>
        </table>
    {assign var=n value=$n+1}
    <button type="button" tabindex="{$n}" class="btn btn-primary btn-block" id="saveEval">Enregistrer</button>
    {assign var=n value=$n+1}
    <div class="form-group">
        <label for="evaluation">Évaluation du professeur</label>
        <textarea name="evaluation" class="form-control" id="editeurEvaluation" tabindex="{$n}">{$evaluationsTravail.commentaire|default:''}</textarea>
    </div>

    <input type="hidden" name="idTravail" value="{$infoTravail.idTravail}">
    <input type="hidden" name="matricule" id="matriculeEval" value="{$matricule}">

</form>

<script type="text/javascript">

    $(document).ready(function(){

        CKEDITOR.replace('editeurEvaluation');

        $("input").tabEnter();

        $('input.cote').first().focus();

        $(".pop").popover({
            trigger:'hover'
            });

        // remplacer la virgule par un point dans la cote
        $(".cote").blur(function(e) {
            laCote = $(this).val().replace(',', '.');
            $(this).val(laCote);
        })

    })

</script>
