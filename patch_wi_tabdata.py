import re

with open('app/Http/Controllers/Inventory/WarehouseIntelligenceController.php', 'r') as f:
    content = f.read()

old_tabdata = """    public function tabData(Request $request)
    {
        $tab = $request->input('tab', 'rts');"""

new_tabdata = """    public function tabData(Request $request)
    {
        $role = strtolower((string)(auth()->user()?->role ?? 'owner'));
        
        $allowedTabs = self::TABS;
        if ($role === 'admin') {
            $allowedTabs = ['rts'];
        } elseif ($role === 'operating') {
            $allowedTabs = ['prd'];
        }

        $tab = $request->input('tab');
        if (!$tab || !in_array($tab, $allowedTabs)) {
            $tab = $allowedTabs[0] ?? 'rts';
        }"""

content = content.replace(old_tabdata, new_tabdata)

with open('app/Http/Controllers/Inventory/WarehouseIntelligenceController.php', 'w') as f:
    f.write(content)

