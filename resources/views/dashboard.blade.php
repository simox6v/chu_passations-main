@extends('layouts.app')

@section('content')
<div class="py-10 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">

    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-extrabold text-gray-900 mb-1">Tableau de bord</h1>
        <p class="text-gray-600 text-base">
            @if(Auth::user()->role === 'admin')
                Bienvenue <span class="font-semibold text-green-600">{{ Auth::user()->name }}</span>,
                vous avez accès à <strong>toutes les passations</strong>.
            @else
                Bonjour <span class="font-semibold text-blue-600">{{ Auth::user()->name }}</span>,
                vous visualisez <strong>l’ensemble des passations</strong>.
            @endif
        </p>
    </div>

    <!-- Filter Form -->
    <form method="GET" action="{{ route('dashboard') }}" class="mb-6 bg-white p-4 rounded-lg shadow flex flex-col md:flex-row gap-4">
        <input
            type="text"
            name="search"
            placeholder="Rechercher nom, prénom ou IP..."
            value="{{ request('search') }}"
            class="w-full md:w-1/3 border border-gray-300 rounded-md px-4 py-2 focus:ring focus:ring-blue-200"
        >

        <select
            name="salle_id"
            class="w-full md:w-1/4 border border-gray-300 rounded-md px-4 py-2 focus:ring focus:ring-blue-200"
        >
            <option value="">-- Toutes les salles --</option>
            @foreach($salles as $salle)
                <option value="{{ $salle->id }}" {{ request('salle_id') == $salle->id ? 'selected' : '' }}>
                    {{ $salle->nom ?? 'Salle #' . $salle->id }}
                </option>
            @endforeach
        </select>

        <button type="submit" class="bg-blue-600 text-white px-5 py-2 rounded-md hover:bg-blue-700 transition">
            Filtrer
        </button>
    </form>

    <!-- Table Display -->
    <div class="overflow-x-auto">
        <div class="min-w-full bg-white rounded-lg shadow">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"></th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">IP</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Patient</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Salle</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Médecin</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Détails</th>
                        @if(Auth::user()->role === 'admin')
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @php
                        $passationsByIp = $passations->groupBy('ip')
                            ->map(fn($group) => $group->sortByDesc('date_passation'))
                            ->sortByDesc(fn($group) => $group->first()->date_passation);
                    @endphp

                    @forelse($passationsByIp as $ip => $ipPassations)
                        @php
                            $lastPassation = $ipPassations->first();
                            $hasMultiple = $ipPassations->count() > 1;
                        @endphp
                        <tr class="cursor-pointer hover:bg-gray-50 transition" data-ip="{{ $ip }}" @if($hasMultiple) onclick="togglePassations('{{ $ip }}')" @endif>
                            <td class="px-3 text-center select-none text-lg font-bold" style="width: 30px;">
                                @if($hasMultiple)
                                    <button
                                        onclick="event.stopPropagation(); togglePassations('{{ $ip }}');"
                                        id="toggleBtn-{{ $ip }}"
                                        aria-expanded="false"
                                        aria-controls="groupRows-{{ $ip }}"
                                        class="focus:outline-none text-black"
                                        title="Afficher/Masquer passations"
                                        type="button"
                                    >&#10148;</button> {{-- ➔ right arrow --}}
                                @endif
                            </td>
                            <td class="px-6 py-3 font-medium text-gray-900">{{ $ip }}</td>
                            <td class="px-6 py-3 font-bold text-gray-900">{{ $lastPassation->nom_patient }} {{ $lastPassation->prenom }}</td>
                            <td class="px-6 py-3 text-sm">
                                <span class="inline-block bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-medium">
                                    {{ $lastPassation->salle->nom ?? 'Non spécifiée' }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-700">
                                <div class="flex items-center space-x-2">
                                    <span>{{ $lastPassation->user->name ?? 'Inconnu' }}</span>
                                    @if($lastPassation->file_attachment)
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800" title="Pièce jointe disponible">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                        </svg>
                                        Fichier
                                    </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-600">{{ \Carbon\Carbon::parse($lastPassation->date_passation)->format('d/m/Y H:i') }}</td>
                            <td class="px-6 py-3 text-center">
                                <a href="#" onclick="event.stopPropagation(); openShowModal({{ $lastPassation->id }});" title="Voir détails">
                                    <img src="{{ asset('images/show.png') }}" alt="Détails" class="inline-block w-6 h-6">
                                </a>
                            </td>
                            @if(Auth::user()->role === 'admin')
                                <td class="px-6 py-3 text-center">
                                    <form action="{{ route('passations.destroy', $lastPassation) }}" method="POST" onsubmit="return confirm('Supprimer cette passation ?');" class="inline-block" onclick="event.stopPropagation();">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Supprimer">
                                            <img src="{{ asset('images/deleted.png') }}" alt="Supprimer" class="inline-block w-6 h-6">
                                        </button>
                                    </form>
                                </td>
                            @endif
                        </tr>

                        @foreach($ipPassations->skip(1) as $passation)
                            <tr class="bg-green-50 hidden groupRow-{{ $ip }}">
                                <td></td>
                                <td class="px-6 py-3 font-medium text-gray-400">-</td>
                                <td class="px-6 py-3 font-medium text-gray-400">-</td>
                                <td class="px-6 py-3 text-sm">
                                    <span class="inline-block bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-medium">
                                        {{ $passation->salle->nom ?? 'Non spécifiée' }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-sm text-gray-700">{{ $passation->user->name ?? 'Inconnu' }}</td>
                                <td class="px-6 py-3 text-sm text-gray-600">{{ \Carbon\Carbon::parse($passation->date_passation)->format('d/m/Y H:i') }}</td>
                                <td class="px-6 py-3 text-center">
                                    <a href="#" onclick="event.stopPropagation(); openShowModal({{ $passation->id }});" title="Voir détails">
                                        <img src="{{ asset('images/show.png') }}" alt="Détails" class="inline-block w-6 h-6">
                                    </a>
                                </td>
                                @if(Auth::user()->role === 'admin')
                                    <td class="px-6 py-3 text-center">
                                        <form action="{{ route('passations.destroy', $passation) }}" method="POST" onsubmit="return confirm('Supprimer cette passation ?');" class="inline-block" onclick="event.stopPropagation();">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Supprimer">
                                                <img src="{{ asset('images/deleted.png') }}" alt="Supprimer" class="inline-block w-6 h-6">
                                            </button>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="{{ Auth::user()->role === 'admin' ? 8 : 7 }}" class="px-6 py-3 text-center text-gray-500">
                                Aucune passation trouvée.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Show Modals --}}
    @foreach($passations as $passation)
    <div
        id="showModal-{{ $passation->id }}"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50 p-4"
        role="dialog" aria-modal="true" aria-labelledby="showModalTitle-{{ $passation->id }}"
    >
        <div class="bg-white rounded-lg shadow-lg w-[80%] h-[80%] p-6 relative flex flex-col max-h-screen overflow-hidden">
            <button class="closeShowModal absolute top-3 right-3 text-gray-700 hover:text-gray-900 text-2xl font-bold" data-id="{{ $passation->id }}" title="Fermer" aria-label="Fermer la fenêtre" type="button">&times;</button>
            <h2 id="showModalTitle-{{ $passation->id }}" class="text-xl font-semibold mb-4">Détails de la passation</h2>

            <div class="flex-1 overflow-auto pr-2 space-y-4 text-gray-700">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <p><strong>IP (Identifiant Patient) :</strong> {{ $passation->ip ?? 'N/A' }}</p>
                    <p><strong>Patient :</strong> {{ $passation->nom_patient }} {{ $passation->prenom }}</p>
                    <p><strong>CIN :</strong> {{ $passation->cin ?? 'N/A' }}</p>
                    <p><strong>Date de la passation :</strong> {{ \Carbon\Carbon::parse($passation->date_passation)->format('d/m/Y H:i') }}</p>
                    <p><strong>Salle :</strong> {{ $passation->salle->nom ?? 'Non spécifiée' }}</p>
                    <p><strong>Médecin :</strong> {{ $passation->user->name ?? 'Inconnu' }}</p>
                </div>

                {{-- Consignes section with gap and proper styling --}}
                <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg shadow p-4 leading-relaxed text-gray-700">
                    <p class="font-semibold mb-3 text-lg">Consignes :</p>
                    <div class="prose prose-sm max-w-none">
                        {!! $passation->description ?? '<p class="text-gray-500 italic">Aucune consigne</p>' !!}
                    </div>
                </div>

                {{-- File attachment section --}}
                @if($passation->file_attachment)
                <div class="mt-6 bg-green-50 border border-green-200 rounded-lg shadow p-4">
                    <p class="font-semibold mb-3 text-lg text-green-800">Pièce jointe :</p>
                    <div class="flex items-center space-x-3">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                        </svg>
                        <span class="text-green-700 font-medium">{{ $passation->file_attachment }}</span>
                        <a href="{{ route('passations.download', $passation) }}" 
                           class="inline-flex items-center px-3 py-1 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 transition-colors">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Télécharger
                        </a>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endforeach

</div>

<script>
function togglePassations(ip) {
    const rows = document.querySelectorAll('.groupRow-' + ip);
    const toggleBtn = document.getElementById('toggleBtn-' + ip);
    if (!rows.length) return;

    const isHidden = rows[0].classList.contains('hidden');
    rows.forEach(row => row.classList.toggle('hidden', !isHidden));

    if (toggleBtn) {
        toggleBtn.innerHTML = isHidden ? '&#9660;' : '&#10148;'; // ▼ down if expanded, ➔ right if collapsed
        toggleBtn.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
    }
}

function openShowModal(id) {
    const modal = document.getElementById('showModal-' + id);
    if (modal) modal.classList.remove('hidden');
}

document.querySelectorAll('.closeShowModal').forEach(btn => {
    btn.addEventListener('click', () => {
        const id = btn.getAttribute('data-id');
        const modal = document.getElementById('showModal-' + id);
        if (modal) modal.classList.add('hidden');
    });
});

document.querySelectorAll('[id^="showModal-"]').forEach(modal => {
    modal.addEventListener('click', e => {
        if (e.target === modal) modal.classList.add('hidden');
    });
});
</script>
@endsection
