<x-erp-card title="CONSIGNACIONES">

    <table class="table table-bordered table-erp">

        <thead class="bg-yellow">

            <tr>

                <th>No.</th>
                <th>VALOR</th>

            </tr>

        </thead>

        <tbody>

            @for($i = 1; $i <= 10; $i++)

            <tr>

                <td>

                    <input type="text"
                           class="form-control erp-input">

                </td>

                <td>

                    <input type="number"
                           class="form-control erp-input">

                </td>

            </tr>

            @endfor

        </tbody>

    </table>

</x-erp-card>
