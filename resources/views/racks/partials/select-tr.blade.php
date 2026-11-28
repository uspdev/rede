{{-- Select da TR do rack, agrupado por prédio. Espera $predios (com telecomRooms) e $selecionada --}}
<div class="mb-3">
    <label for="telecom_room_id" class="form-label">Telecommunications Room *</label>
    @if($predios->isEmpty())
        <div class="alert alert-warning mb-0">
            Nenhuma Telecommunications Room cadastrada. Crie uma na página do prédio antes de cadastrar racks.
        </div>
    @else
        <select class="form-select" id="telecom_room_id" name="telecom_room_id" required>
            <option value="">Selecione a Telecommunications Room</option>
            @foreach($predios as $predio)
                <optgroup label="{{ $predio->nome }}">
                    @foreach($predio->telecomRooms as $tr)
                        <option value="{{ $tr->id }}" @selected((string) old('telecom_room_id', $selecionada) === (string) $tr->id)>
                            {{ $tr->nome }} — {{ $predio->nome }}
                        </option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
    @endif
</div>
