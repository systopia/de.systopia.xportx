<table>
    <thead>
        <tr>
            <th colspan="5">
                {* TODO: kompletter header hier, damit er sich wiederholt *}
                <span>{$first_record.Veranstaltungstitel} | {$first_record.Datum|date_format:"%d.%m.%Y"} | {$first_record.Rolle}</span>
            </th>
        </tr>
        <tr>
            <th>Nachname / Surname</th>
            <th>Vorname / Name</th>
            <th>Organisation</th>
            <th>E-Mail</th>
            <th>Unterschrift /Signature</th>
        </tr>
    </thead>
    <tbody>
        {foreach from=$records item=record}
            <tr>
                <td>{$record.Nachname}</td>
                <td>{$record.Vorname}</td>
                <td>{$record.Organisation1} {$record.Organisation2}</td>
                <td>{$record.EMail}</td>
                <td></td>
            </tr>
        {/foreach}
    </tbody>
</table>