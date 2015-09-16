@extends('_layouts.admin')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-12 text-center page-header">
                <h1>Allopass</h1>
                <small>Historique des codes allopass</small>
            </div>
            <div class="col-md-4 col-md-offset-4">
                <table class="table">
                    <thead>
                    <tr>
                        <th class="text-center">Code</th>
                        <th class="text-center">Compte</th>
                        <th class="text-center">Date</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($allopass as $code)
                        <tr>
                            <th scope="row" class="text-center">{{$code->id_allopass}}</th>
                            <td class="text-center">{{$code->id_account}}</td>
                            @if (Carbon::parse($code->created_at)->isToday())
                                <td class="text-center text-success">{{$code->created_at}}</td>
                            @else
                                <td class="text-center">{{$code->created_at}}</td>
                            @endif
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@stop
