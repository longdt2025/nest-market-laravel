param([switch]$SkipBuild)
$ErrorActionPreference = 'Stop'
Push-Location (Split-Path -Parent $PSScriptRoot)

function Invoke-Docker {
    & docker @args
    if ($LASTEXITCODE -ne 0) {
        throw "Docker command failed: docker $($args -join ' ')"
    }
}

try {
    if (-not (Test-Path -LiteralPath '.env')) {
        Copy-Item -LiteralPath '.env.example' -Destination '.env'
    }

    Invoke-Docker compose config --quiet
    if (-not $SkipBuild) {
        Invoke-Docker compose build app
    }

    Invoke-Docker compose up -d --wait mysql
    Invoke-Docker compose run --rm setup
    Invoke-Docker compose up -d --wait app nginx
    Invoke-Docker compose ps
    Write-Host 'NEST Market is ready. Default URL: http://localhost:8082 (DOCKER_APP_URL in .env).'
} finally {
    Pop-Location
}
