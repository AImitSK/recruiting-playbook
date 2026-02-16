$source = 'C:\Users\StefanKühne\Desktop\Projekte\recruiting-playbook\plugin'
$dest = 'C:\Users\StefanKühne\Desktop\recruiting-playbook.zip'

# Remove old zip
if (Test-Path $dest) { Remove-Item $dest }

# Create temp folder
$temp = 'C:\Users\StefanKühne\Desktop\recruiting-playbook-temp'
if (Test-Path $temp) { Remove-Item $temp -Recurse -Force }
New-Item -ItemType Directory -Path $temp | Out-Null
New-Item -ItemType Directory -Path "$temp\recruiting-playbook" | Out-Null

# Copy files excluding node_modules and tests
Get-ChildItem $source | Where-Object { $_.Name -notin @('node_modules', 'tests') } | ForEach-Object {
    Copy-Item $_.FullName -Destination "$temp\recruiting-playbook" -Recurse -Force
}

# Remove dev vendor folders
$devFolders = @('phpunit','mockery','brain','hamcrest','phpstan','squizlabs','phpcsstandards','dealerdirect','wp-coding-standards','php-stubs','sebastian','nikic','phar-io','myclabs','antecedent','bin')
foreach ($f in $devFolders) {
    $path = "$temp\recruiting-playbook\vendor\$f"
    if (Test-Path $path) { Remove-Item $path -Recurse -Force }
}

# Remove source files and maps
Get-ChildItem "$temp\recruiting-playbook" -Recurse -Include '*.map' | Remove-Item -Force
if (Test-Path "$temp\recruiting-playbook\assets\src") { Remove-Item "$temp\recruiting-playbook\assets\src" -Recurse -Force }

# Create zip
Compress-Archive -Path "$temp\recruiting-playbook" -DestinationPath $dest -Force

# Cleanup
Remove-Item $temp -Recurse -Force

# Show result
$file = Get-Item $dest
Write-Host "ZIP erstellt: $($file.Name) - $([math]::Round($file.Length / 1MB, 2)) MB"
