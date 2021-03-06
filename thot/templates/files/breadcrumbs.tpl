<input type="hidden" name="arborescence" id="arborescence" value="{$arborescence|default:''}">

{if $listeDirs == Null}
    <button type="button" class="btn btn-primary btn-sm btn-crumb" data-dir="">
        {$acronyme}
    </button>
{else}
    {foreach from=$listeDirs key=dir item=path}
        <button type="button" class="btn btn-primary btn-sm btn-crumb {if $dir == $directory}active{/if}" data-dir="/{$path}">
            {if $dir == ''}{$acronyme}{else}{$dir}{/if}
        </button>
    {/foreach}
{/if}

<button type="button" class="btn btn-success" id="btn-changeView" data-view="liste">Changer la vue</button>

<div class="btn-group pull-right">
    <button type="button" class="btn btn-danger" id="btn-mkdir" title="Créer un dossier"><i class="fa fa-plus"></i> <i class="fa fa-folder-open-o"></i></button>
    <button type="button" class="btn btn-info" id="btn-upload" title="Ajouter un document"><i class="fa fa-plus"></i> <i class="fa fa-file-o"></i></button>
</div>
