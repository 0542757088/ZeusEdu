{function name=repertoire level=0}
<ul class="filetree level{$level}" {if ($level > 0)}style="display:none"{/if}>

    {foreach $data as $file}
        {if $file.type == 'folder'}
            <li class="folder expanded">
                <span class="delDir pull-right text-danger">&nbsp;<i class="fa fa-times"></i><i class="fa fa-exclamation"></i></span>
                <a href="javascript:void(0)"
                    class="dirLink"
                    data-dir="{if $level > 0}/{/if}{$file.path|escape:'htmlall'}/{$file.name|escape:'htmlall'}/"
                    data-nbfiles='{$file.items|@count}'>
                    {$file.name}
                </a>

                {repertoire data=$file.items level=$level+1}

            </li>
        {else}
            <li data-filename="{$file.name|escape:'htmlall'}"
                data-extension='{$file.ext}'
                data-path="/{$file.path|escape:'htmlall'}"
                data-size='{$file.size}'
                data-date='{$file.date}'
                class='file ext_{$file.ext} level{$level}'>
                <a href="inc/download.php?type=pfNid&amp;file={$file.path|escape:'htmlall'}/{$file.name|escape:'htmlall'}&amp;f={$fileId}">
                    {$file.name|escape:'htmlall'}
                </a>

            </li>
        {/if}
    {/foreach}
</ul>
{/function}


{repertoire data=$tree}