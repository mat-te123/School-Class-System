<x-app-layout title="Periode" type="siswa">
    <span>
        @dump($formattedMenus)
    </span>
    <pre>{{ json_encode($activePeriods, JSON_PRETTY_PRINT) }}</pre>

</x-app-layout>
