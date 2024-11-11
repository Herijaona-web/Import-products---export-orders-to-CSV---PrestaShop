{if isset($orders) && $orders|@count > 0}
    <div class="table-responsive table-export-viti">
        <table class="table table-bordered table-striped">
            <thead>
                <tr class="table-primary">
                    <th class="text-center">ID</th>
                    <th class="text-center">Référence</th>
                    <th class="text-center">Nouveau Client</th>
                    <th class="text-center">Nom Client</th>
                    <th class="text-center">Total</th>
                    <th class="text-center">Paiement</th>
                    <th class="text-center">Date</th>
                    <th class="text-center"></th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$orders item=order}
                    <tr>
                        <td class="text-center">{$order.id_order}</td>
                        <td class="text-center">{$order.reference}</td>
                        <td class="text-center">{if $order.nouveau_client}Oui{else}Non{/if}</td>
                        <td class="text-center">{$order.client_name}</td>
                        <td class="text-center"><span class="badge badge-success rounded">{convertPrice price=$order.total}</span></td>
                        <td class="text-center">{$order.payment}</td>
                        <td class="text-center">{$order.order_date|date_format:"%d/%m/%Y %H:%M"}</td>
                        <td class="text-center"><a href={$order.link} style="background: #0058cb;padding: 7px;color: white;font-weight: 600;border-radius: 3px;" class="download-csv" href="">Télécharger</a></td>
                    </tr>
                {/foreach}
            </tbody>
        </table>
    </div>
{else}
    <div class="alert alert-warning" role="alert">
        Aucune commande à afficher.
    </div>
{/if}