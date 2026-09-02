<form method="get" action="{{ route('home') }}">
    <div class="row">
        <div class=" col-sm input-group">
        <input type="text" class="form-control" id="macaddress" name="search" placeholder="Buscar Mac Address..." value="{{ request()->search }}">

        <span class="input-group-btn">
            <button type="submit" class="btn btn-success"> Buscar </button>
        </span>

        </div>
    </div>
</form>

@section('javascripts_bottom')
<script>
var macAddress = document.getElementById("macaddress");
function formatMAC(e) {
    var r = /([a-f0-9]{2})([a-f0-9]{2})/i,
        str = e.target.value.replace(/[^a-f0-9]/ig, "");
        str = str.toUpperCase();

    while (r.test(str)) {
        str = str.replace(r, '$1' + ':' + '$2');
    }

    e.target.value = str.slice(0, 17);
};
macAddress.addEventListener("keyup", formatMAC, false);
</script>
@endsection
