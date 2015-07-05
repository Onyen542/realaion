@extends('_layouts.master')

@section('content')
    <div class="container_single">
        <div class="container_single_top">
            <h1>Top BG page</h1>
        </div>
        <div class="container_single_body">
            <table>
                <thead>
                <tr>
                    <th>#</th>
                    <th>Nom</th>
                    <th>Points</th>
                    <td>Race</td>
                    <th>Classe</th>
                    <th>Victoire</th>
                    <th>Défaite</th>
                </tr>
                </thead>
                <tbody>
                @foreach($top as $index => $player)
                    <tr>
                        <td class="strong">{{$index + 1}}</td>
                        <td>{{$player->name['name']}}</td>
                        <td>{{$player->rating}}</td>
                        <td><span class="{{Lang::get('aion.race_logo.'.$player->name['race'])}}"></span></td>
                        <td><span class="charactericon-class {{Lang::get('aion.class_logo.'.$player->name['player_class'])}}"></span></td>
                        <td>{{$player->wins}}</td>
                        <td>{{$player->losses}}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@stop
