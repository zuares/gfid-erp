import re

with open('app/Http/Controllers/Inventory/WarehouseIntelligenceController.php', 'r') as f:
    content = f.read()

# Replace the index method tab logic
old_index_logic = """    public function index(Request $request)
    {
        $tab = $request->query('tab', 'rts');
        if (!in_array($tab, self::TABS)) {
            $tab = 'rts';
        }"""

new_index_logic = """    public function index(Request $request)
    {
        $role = strtolower((string)(auth()->user()?->role ?? 'owner'));
        
        // Define allowed tabs per role
        $allowedTabs = self::TABS;
        if ($role === 'admin') {
            $allowedTabs = ['rts'];
        } elseif ($role === 'operating') {
            $allowedTabs = ['prd'];
        }

        $tab = $request->query('tab');
        if (!$tab || !in_array($tab, $allowedTabs)) {
            $tab = $allowedTabs[0] ?? 'rts';
        }"""

content = content.replace(old_index_logic, new_index_logic)

with open('app/Http/Controllers/Inventory/WarehouseIntelligenceController.php', 'w') as f:
    f.write(content)

