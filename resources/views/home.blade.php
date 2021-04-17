@include('layouts.app')


<div class="content">
    <form method="POST" action="{{ 'aaa' }}">
        @csrf
        <label class="form-inline">
            <input class="btn btn-default form-control" type="submit" name="btnMode" value="CSV">
            <input class="btn btn-default form-control" type="submit" name="btnMode" value="Delete">
        </label>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th scope="col">IP</th>
                    <th scope="col">MAC</th>
                    <th scope="col">Hostname</th>
                    <th scope="col">Description</th>
                    <th scope="col">isLeased</th>
                    <th scope="col">isConfig</th>
                </tr>
            </thead>
            @foreach ($devices as $device)
                <tbody>
                    <tr>
                        <td>{{ $device->ip }}</td>
                        <td>{{ $device->mac }}</td>
                        <td>{{ $device->hostname }}</td>
                        <td>{{ $device->description }}</td>
                        @if ($device->isLease == true)
                            <td>True</td>
                        @else
                            <td>False</td>
                        @endif

                        @if ($device->isConfig == true)
                            <td>True</td>
                        @else
                            <td>False</td>
                        @endif
                    </tr>
                </tbody>
            @endforeach
        </table>
    </form>
</div>