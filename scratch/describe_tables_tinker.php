$tables = [
    'tes_tenencia_armas',
    'tes_porte_armas',
    'tes_prendas',
    'tes_eventuales',
    'tes_arrendamientos',
    'tes_deposito_vehiculos',
    'tes_multas_cobradas'
];

foreach ($tables as $table) {
    echo "--- TABLE: $table ---\n";
    if (Schema::hasTable($table)) {
        $columns = Schema::getColumnListing($table);
        foreach ($columns as $column) {
            $colInfo = DB::select("SHOW COLUMNS FROM `$table` WHERE Field = ?", [$column]);
            $isNullable = !empty($colInfo) && $colInfo[0]->Null === 'YES';
            echo "  $column: " . ($isNullable ? 'nullable' : 'NOT NULL') . "\n";
        }
    } else {
        echo "  Table does not exist!\n";
    }
}
