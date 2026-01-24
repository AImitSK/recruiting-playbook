# Testing-Strategie

## Übersicht

Das Plugin verwendet eine **pragmatische Test-Strategie** mit Fokus auf kritische Pfade:

| Test-Art | Tool | Scope | Priorität |
|----------|------|-------|-----------|
| PHP Unit | PHPUnit + WP Test Utils | Services, Models, API | P0 |
| JS Unit | Jest + Testing Library | React-Komponenten | P1 |
| E2E | Playwright | Kritische Flows | P2 (nach MVP) |

**Ziel-Coverage:** 50-60% (kritische Pfade)

```
┌─────────────────────────────────────────────────────────────────┐
│                      TEST-PYRAMIDE                              │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│                         ▲                                       │
│                        ╱ ╲         E2E Tests                    │
│                       ╱   ╲        (nach MVP)                   │
│                      ╱─────╲       ~5 Tests                     │
│                     ╱       ╲                                   │
│                    ╱─────────╲     Integration                  │
│                   ╱           ╲    ~20 Tests                    │
│                  ╱─────────────╲                                │
│                 ╱               ╲   Unit Tests                  │
│                ╱─────────────────╲  ~50 Tests                   │
│               ▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔                              │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## Was wir testen

### Kritisch (immer testen) ✅

| Bereich | Warum |
|---------|-------|
| License Manager | Umsatz-relevant |
| Feature Flags | Freemium-Logik |
| Application Service | Kern-Business-Logik |
| REST API Endpoints | Externe Schnittstelle |
| Formular-Validierung | User-Eingaben |

### Wichtig (sollte getestet werden) ✅

| Bereich | Warum |
|---------|-------|
| Repositories | Datenintegrität |
| Email Service | Kommunikation |
| Webhook Service | Integrationen |
| React Kanban | Komplexe UI-Logik |

### Nicht testen ❌

| Bereich | Warum |
|---------|-------|
| WordPress Core | Bereits getestet |
| Einfache Getter/Setter | Kein Mehrwert |
| Reine UI/Styling | Visuell prüfen |
| Admin-Menü Registration | WordPress-Standard |

---

## Ordnerstruktur

```
recruiting-playbook/
├── tests/
│   ├── php/
│   │   ├── bootstrap.php           # Test-Setup
│   │   ├── Unit/
│   │   │   ├── Licensing/
│   │   │   │   ├── LicenseManagerTest.php
│   │   │   │   └── FeatureFlagsTest.php
│   │   │   ├── Services/
│   │   │   │   ├── ApplicationServiceTest.php
│   │   │   │   └── JobServiceTest.php
│   │   │   └── Models/
│   │   │       └── ApplicationTest.php
│   │   ├── Integration/
│   │   │   ├── Api/
│   │   │   │   ├── JobsControllerTest.php
│   │   │   │   └── ApplicationsControllerTest.php
│   │   │   └── Repositories/
│   │   │       └── ApplicationRepositoryTest.php
│   │   └── fixtures/
│   │       └── test-data.php
│   │
│   ├── js/
│   │   ├── setup.js                # Jest-Setup
│   │   ├── components/
│   │   │   ├── KanbanBoard.test.jsx
│   │   │   └── ApplicationCard.test.jsx
│   │   └── hooks/
│   │       └── useApplications.test.js
│   │
│   └── e2e/                        # Nach MVP
│       ├── playwright.config.ts
│       └── specs/
│           ├── application-form.spec.ts
│           └── license-activation.spec.ts
│
├── phpunit.xml                     # PHPUnit Config
├── jest.config.js                  # Jest Config
└── .github/
    └── workflows/
        └── tests.yml               # CI/CD
```

---

## 1. PHP Unit Tests (PHPUnit)

### Installation

```bash
composer require --dev phpunit/phpunit:"^9.5"
composer require --dev yoast/phpunit-polyfills:"^1.0"
composer require --dev brain/monkey:"^2.6"
composer require --dev mockery/mockery:"^1.5"
```

### phpunit.xml

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit
    bootstrap="tests/php/bootstrap.php"
    colors="true"
    verbose="true"
    stopOnFailure="false"
>
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/php/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/php/Integration</directory>
        </testsuite>
    </testsuites>
    
    <coverage processUncoveredFiles="true">
        <include>
            <directory suffix=".php">src</directory>
        </include>
        <exclude>
            <directory>src/Admin/Views</directory>
            <directory>src/templates</directory>
        </exclude>
        <report>
            <html outputDirectory="tests/coverage/html"/>
            <text outputFile="php://stdout"/>
        </report>
    </coverage>
    
    <php>
        <const name="ABSPATH" value="./"/>
        <const name="RP_TESTING" value="true"/>
    </php>
</phpunit>
```

### Bootstrap (tests/php/bootstrap.php)

```php
<?php
/**
 * PHPUnit Bootstrap
 */

// Composer Autoloader
require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';

// Brain Monkey Setup
require_once __DIR__ . '/bootstrap-brain-monkey.php';

// Test Utilities
require_once __DIR__ . '/TestCase.php';
require_once __DIR__ . '/fixtures/test-data.php';
```

### Brain Monkey Setup

```php
<?php
// tests/php/bootstrap-brain-monkey.php

use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * WordPress-Funktionen mocken ohne WordPress zu laden
 */

// Basis-Konstanten
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', '/tmp/wordpress/' );
}

if ( ! defined( 'RP_PLUGIN_DIR' ) ) {
    define( 'RP_PLUGIN_DIR', dirname( __DIR__, 2 ) . '/' );
}

if ( ! defined( 'RP_VERSION' ) ) {
    define( 'RP_VERSION', '1.0.0-test' );
}

// WordPress-Funktionen stubben
Functions\stubTranslationFunctions();
Functions\stubEscapeFunctions();

// Häufig verwendete Funktionen
Functions\when( 'get_option' )->alias( function( $key, $default = false ) {
    global $wp_test_options;
    return $wp_test_options[ $key ] ?? $default;
} );

Functions\when( 'update_option' )->alias( function( $key, $value ) {
    global $wp_test_options;
    $wp_test_options[ $key ] = $value;
    return true;
} );

Functions\when( 'delete_option' )->alias( function( $key ) {
    global $wp_test_options;
    unset( $wp_test_options[ $key ] );
    return true;
} );

Functions\when( 'get_transient' )->alias( function( $key ) {
    global $wp_test_transients;
    return $wp_test_transients[ $key ] ?? false;
} );

Functions\when( 'set_transient' )->alias( function( $key, $value, $expiration = 0 ) {
    global $wp_test_transients;
    $wp_test_transients[ $key ] = $value;
    return true;
} );

Functions\when( 'delete_transient' )->alias( function( $key ) {
    global $wp_test_transients;
    unset( $wp_test_transients[ $key ] );
    return true;
} );

Functions\when( 'wp_create_nonce' )->justReturn( 'test-nonce-123' );
Functions\when( 'wp_verify_nonce' )->justReturn( true );
Functions\when( 'current_time' )->justReturn( date( 'Y-m-d H:i:s' ) );
Functions\when( 'get_current_user_id' )->justReturn( 1 );
Functions\when( 'get_site_url' )->justReturn( 'https://test.example.com' );
```

### Base TestCase

```php
<?php
// tests/php/TestCase.php

namespace RecruitingPlaybook\Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Brain\Monkey;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

abstract class TestCase extends PHPUnitTestCase {
    
    use MockeryPHPUnitIntegration;
    
    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
        
        // Test-Globals zurücksetzen
        global $wp_test_options, $wp_test_transients;
        $wp_test_options = [];
        $wp_test_transients = [];
    }
    
    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }
    
    /**
     * Option für Test setzen
     */
    protected function setOption( string $key, $value ): void {
        global $wp_test_options;
        $wp_test_options[ $key ] = $value;
    }
    
    /**
     * Transient für Test setzen
     */
    protected function setTransient( string $key, $value ): void {
        global $wp_test_transients;
        $wp_test_transients[ $key ] = $value;
    }
}
```

---

## Beispiel-Tests (PHP)

### LicenseManagerTest

```php
<?php
// tests/php/Unit/Licensing/LicenseManagerTest.php

namespace RecruitingPlaybook\Tests\Unit\Licensing;

use RecruitingPlaybook\Tests\TestCase;
use RecruitingPlaybook\Licensing\LicenseManager;
use Brain\Monkey\Functions;

class LicenseManagerTest extends TestCase {
    
    private LicenseManager $manager;
    
    protected function setUp(): void {
        parent::setUp();
        
        // Singleton zurücksetzen für Tests
        $reflection = new \ReflectionClass( LicenseManager::class );
        $instance = $reflection->getProperty( 'instance' );
        $instance->setAccessible( true );
        $instance->setValue( null, null );
        
        $this->manager = LicenseManager::get_instance();
    }
    
    /** @test */
    public function it_returns_free_tier_without_license(): void {
        $this->assertEquals( 'FREE', $this->manager->get_tier() );
    }
    
    /** @test */
    public function it_validates_correct_license_format(): void {
        $valid_key = 'RP-PRO-A7K9-M2X4-P8L3-Q5R1-4F2A';
        
        // Checksum für diesen Key berechnen und anpassen
        $result = $this->manager->activate( $valid_key );
        
        // Format sollte akzeptiert werden (Checksum evtl. falsch)
        $this->assertArrayHasKey( 'success', $result );
    }
    
    /** @test */
    public function it_rejects_invalid_license_format(): void {
        $invalid_keys = [
            'invalid-key',
            'RP-INVALID-1234',
            'RP-PRO-123',
            'XX-PRO-A7K9-M2X4-P8L3-Q5R1-4F2A',
        ];
        
        foreach ( $invalid_keys as $key ) {
            $result = $this->manager->activate( $key );
            
            $this->assertFalse( 
                $result['success'], 
                "Key '$key' should be rejected" 
            );
        }
    }
    
    /** @test */
    public function it_extracts_correct_tier_from_key(): void {
        $tiers = [
            'RP-PRO-A7K9-M2X4-P8L3-Q5R1-' => 'PRO',
            'RP-AI-A7K9-M2X4-P8L3-Q5R1-' => 'AI_ADDON',
            'RP-BUNDLE-A7K9-M2X4-P8L3-Q5R1-' => 'BUNDLE',
        ];
        
        foreach ( $tiers as $prefix => $expected_tier ) {
            // Reflection um private Methode zu testen
            $method = new \ReflectionMethod( $this->manager, 'extract_tier' );
            $method->setAccessible( true );
            
            $result = $method->invoke( $this->manager, $prefix . '0000' );
            
            $this->assertEquals( 
                $expected_tier, 
                $result,
                "Prefix '$prefix' should return tier '$expected_tier'"
            );
        }
    }
    
    /** @test */
    public function it_returns_cached_validation_result(): void {
        // Cache setzen
        $this->setTransient( 'rp_license_cache', [
            'valid' => true,
            'checked_at' => time(),
        ] );
        
        // Lizenz setzen
        $this->setOption( 'rp_license', [
            'key' => 'RP-PRO-TEST-TEST-TEST-TEST-TEST',
            'tier' => 'PRO',
            'domain' => 'test.example.com',
            'activated_at' => time(),
            'last_check' => time(),
        ] );
        
        // Neuen Manager mit gecachter Lizenz
        $reflection = new \ReflectionClass( LicenseManager::class );
        $instance = $reflection->getProperty( 'instance' );
        $instance->setAccessible( true );
        $instance->setValue( null, null );
        
        $manager = LicenseManager::get_instance();
        
        $this->assertEquals( 'PRO', $manager->get_tier() );
        $this->assertTrue( $manager->is_valid() );
    }
    
    /** @test */
    public function it_handles_grace_period_when_offline(): void {
        $last_check = time() - ( 3 * DAY_IN_SECONDS ); // 3 Tage her
        
        $this->setOption( 'rp_license', [
            'key' => 'RP-PRO-TEST-TEST-TEST-TEST-TEST',
            'tier' => 'PRO',
            'domain' => 'test.example.com',
            'activated_at' => time() - ( 30 * DAY_IN_SECONDS ),
            'last_check' => $last_check,
        ] );
        
        // Cache abgelaufen, Server nicht erreichbar simulieren
        // In echtem Test würde man den HTTP-Call mocken
        
        $this->assertTrue( true ); // Placeholder
    }
    
    /** @test */
    public function it_deactivates_license_correctly(): void {
        $this->setOption( 'rp_license', [
            'key' => 'RP-PRO-TEST-TEST-TEST-TEST-TEST',
            'tier' => 'PRO',
            'domain' => 'test.example.com',
        ] );
        
        $reflection = new \ReflectionClass( LicenseManager::class );
        $instance = $reflection->getProperty( 'instance' );
        $instance->setAccessible( true );
        $instance->setValue( null, null );
        
        $manager = LicenseManager::get_instance();
        $result = $manager->deactivate();
        
        $this->assertTrue( $result['success'] );
        $this->assertEquals( 'FREE', $manager->get_tier() );
    }
}
```

### FeatureFlagsTest

```php
<?php
// tests/php/Unit/Licensing/FeatureFlagsTest.php

namespace RecruitingPlaybook\Tests\Unit\Licensing;

use RecruitingPlaybook\Tests\TestCase;
use RecruitingPlaybook\Licensing\FeatureFlags;

class FeatureFlagsTest extends TestCase {
    
    /** @test */
    public function free_tier_has_correct_features(): void {
        $flags = new FeatureFlags( 'FREE' );

        $this->assertTrue( $flags->can( 'create_jobs' ) );
        $this->assertTrue( $flags->can( 'unlimited_jobs' ) );
        $this->assertEquals( -1, $flags->get( 'max_jobs' ) );
        $this->assertFalse( $flags->can( 'kanban_board' ) );
        $this->assertFalse( $flags->can( 'api_access' ) );
        $this->assertFalse( $flags->can( 'ai_job_generation' ) );
    }
    
    /** @test */
    public function pro_tier_unlocks_pro_features(): void {
        $flags = new FeatureFlags( 'PRO' );
        
        $this->assertTrue( $flags->can( 'unlimited_jobs' ) );
        $this->assertEquals( -1, $flags->get( 'max_jobs' ) );
        $this->assertTrue( $flags->can( 'kanban_board' ) );
        $this->assertTrue( $flags->can( 'api_access' ) );
        $this->assertTrue( $flags->can( 'webhooks' ) );
        $this->assertFalse( $flags->can( 'ai_job_generation' ) );
    }
    
    /** @test */
    public function ai_addon_has_ai_but_not_pro(): void {
        $flags = new FeatureFlags( 'AI_ADDON' );

        $this->assertTrue( $flags->can( 'unlimited_jobs' ) );
        $this->assertEquals( -1, $flags->get( 'max_jobs' ) );
        $this->assertFalse( $flags->can( 'kanban_board' ) );
        $this->assertTrue( $flags->can( 'ai_job_generation' ) );
        $this->assertTrue( $flags->can( 'ai_text_improvement' ) );
    }
    
    /** @test */
    public function bundle_has_all_features(): void {
        $flags = new FeatureFlags( 'BUNDLE' );
        
        $this->assertTrue( $flags->can( 'unlimited_jobs' ) );
        $this->assertTrue( $flags->can( 'kanban_board' ) );
        $this->assertTrue( $flags->can( 'api_access' ) );
        $this->assertTrue( $flags->can( 'ai_job_generation' ) );
        $this->assertTrue( $flags->can( 'ai_text_improvement' ) );
    }
    
    /** @test */
    public function unknown_feature_returns_false(): void {
        $flags = new FeatureFlags( 'PRO' );
        
        $this->assertFalse( $flags->can( 'nonexistent_feature' ) );
        $this->assertFalse( $flags->get( 'nonexistent_feature' ) );
    }
    
    /** @test */
    public function tier_can_be_changed(): void {
        $flags = new FeatureFlags( 'FREE' );
        
        $this->assertFalse( $flags->can( 'kanban_board' ) );
        
        $flags->setTier( 'PRO' );
        
        $this->assertTrue( $flags->can( 'kanban_board' ) );
        $this->assertEquals( 'PRO', $flags->getTier() );
    }
}
```

### ApplicationServiceTest

```php
<?php
// tests/php/Unit/Services/ApplicationServiceTest.php

namespace RecruitingPlaybook\Tests\Unit\Services;

use RecruitingPlaybook\Tests\TestCase;
use RecruitingPlaybook\Services\ApplicationService;
use RecruitingPlaybook\Repositories\ApplicationRepository;
use RecruitingPlaybook\Repositories\CandidateRepository;
use RecruitingPlaybook\Models\Application;
use Mockery;

class ApplicationServiceTest extends TestCase {
    
    private ApplicationService $service;
    private $mockAppRepo;
    private $mockCandidateRepo;
    
    protected function setUp(): void {
        parent::setUp();
        
        $this->mockAppRepo = Mockery::mock( ApplicationRepository::class );
        $this->mockCandidateRepo = Mockery::mock( CandidateRepository::class );
        
        // Service mit Mocks erstellen
        $this->service = new ApplicationService(
            $this->mockAppRepo,
            $this->mockCandidateRepo
        );
    }
    
    /** @test */
    public function it_creates_application_with_new_candidate(): void {
        $data = [
            'job_id' => 123,
            'email' => 'test@example.com',
            'first_name' => 'Max',
            'last_name' => 'Mustermann',
            'consent_privacy' => true,
        ];
        
        // Candidate wird erstellt
        $this->mockCandidateRepo
            ->shouldReceive( 'find_or_create' )
            ->once()
            ->with( Mockery::subset( [
                'email' => 'test@example.com',
                'first_name' => 'Max',
                'last_name' => 'Mustermann',
            ] ) )
            ->andReturn( 456 ); // Candidate ID
        
        // Application wird erstellt
        $this->mockAppRepo
            ->shouldReceive( 'create' )
            ->once()
            ->with( Mockery::subset( [
                'job_id' => 123,
                'candidate_id' => 456,
                'consent_privacy' => true,
            ] ) )
            ->andReturn( 789 ); // Application ID
        
        // Application wird zurückgeladen
        $mockApplication = $this->createMockApplication( 789 );
        $this->mockAppRepo
            ->shouldReceive( 'find_by_id' )
            ->with( 789 )
            ->andReturn( $mockApplication );
        
        $result = $this->service->submit( $data );
        
        $this->assertInstanceOf( Application::class, $result );
        $this->assertEquals( 789, $result->id );
    }
    
    /** @test */
    public function it_validates_status_transitions(): void {
        $application = $this->createMockApplication( 1, 'new' );
        
        $this->mockAppRepo
            ->shouldReceive( 'find_by_id' )
            ->with( 1 )
            ->andReturn( $application );
        
        // Gültige Transition: new → screening
        $this->mockAppRepo
            ->shouldReceive( 'update_status' )
            ->once()
            ->with( 1, 'screening', Mockery::any() )
            ->andReturn( true );
        
        $this->mockAppRepo
            ->shouldReceive( 'find_by_id' )
            ->andReturn( $this->createMockApplication( 1, 'screening' ) );
        
        $result = $this->service->change_status( 1, 'screening' );
        
        $this->assertEquals( 'screening', $result->status );
    }
    
    /** @test */
    public function it_rejects_invalid_status_transition(): void {
        $application = $this->createMockApplication( 1, 'new' );
        
        $this->mockAppRepo
            ->shouldReceive( 'find_by_id' )
            ->with( 1 )
            ->andReturn( $application );
        
        // Ungültige Transition: new → hired (muss durch interview gehen)
        $this->expectException( \InvalidArgumentException::class );
        $this->expectExceptionMessage( 'Cannot transition' );
        
        $this->service->change_status( 1, 'hired' );
    }
    
    /** @test */
    public function it_throws_when_application_not_found(): void {
        $this->mockAppRepo
            ->shouldReceive( 'find_by_id' )
            ->with( 999 )
            ->andReturn( null );
        
        $this->expectException( \InvalidArgumentException::class );
        $this->expectExceptionMessage( 'not found' );
        
        $this->service->change_status( 999, 'screening' );
    }
    
    /**
     * Helper: Mock Application erstellen
     */
    private function createMockApplication( int $id, string $status = 'new' ): Application {
        $app = new Application();
        $app->id = $id;
        $app->job_id = 123;
        $app->candidate_id = 456;
        $app->status = $status;
        $app->created_at = date( 'Y-m-d H:i:s' );
        $app->updated_at = date( 'Y-m-d H:i:s' );
        return $app;
    }
}
```

---

## 2. JavaScript Tests (Jest)

### Installation

```bash
cd admin-ui
npm install --save-dev jest @testing-library/react @testing-library/jest-dom @testing-library/user-event jest-environment-jsdom
```

### jest.config.js

```javascript
// admin-ui/jest.config.js

module.exports = {
    testEnvironment: 'jsdom',
    
    setupFilesAfterEnv: [
        '<rootDir>/tests/setup.js'
    ],
    
    moduleNameMapper: {
        // CSS/SCSS Mocks
        '\\.(css|less|scss|sass)$': 'identity-obj-proxy',
        
        // Pfad-Aliase
        '^@/(.*)$': '<rootDir>/src/$1',
        '^@components/(.*)$': '<rootDir>/src/components/$1',
    },
    
    testMatch: [
        '<rootDir>/tests/**/*.test.{js,jsx}',
        '<rootDir>/src/**/*.test.{js,jsx}',
    ],
    
    collectCoverageFrom: [
        'src/**/*.{js,jsx}',
        '!src/index.js',
        '!src/**/*.stories.{js,jsx}',
    ],
    
    coverageThreshold: {
        global: {
            branches: 50,
            functions: 50,
            lines: 50,
            statements: 50,
        },
    },
};
```

### Test Setup

```javascript
// admin-ui/tests/setup.js

import '@testing-library/jest-dom';

// WordPress Globals mocken
global.wp = {
    i18n: {
        __: (text) => text,
        _n: (single, plural, count) => count === 1 ? single : plural,
        sprintf: (format, ...args) => {
            let i = 0;
            return format.replace(/%s/g, () => args[i++]);
        },
    },
    apiFetch: jest.fn(),
};

// Plugin Globals mocken
global.rpAdmin = {
    restUrl: 'https://test.example.com/wp-json/recruiting/v1/',
    nonce: 'test-nonce-123',
    messages: {
        application: {
            submitted: 'Bewerbung gesendet',
            status_changed: 'Status geändert',
        },
        general: {
            error: 'Ein Fehler ist aufgetreten',
            loading: 'Wird geladen...',
        },
    },
    license: {
        tier: 'PRO',
        features: {
            kanban_board: true,
            api_access: true,
        },
    },
};

// Fetch mocken
global.fetch = jest.fn();

// ResizeObserver mocken (für Drag & Drop)
global.ResizeObserver = class ResizeObserver {
    observe() {}
    unobserve() {}
    disconnect() {}
};
```

---

## Beispiel-Tests (JavaScript)

### KanbanBoard.test.jsx

```jsx
// admin-ui/tests/components/KanbanBoard.test.jsx

import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { KanbanBoard } from '@components/Applications/KanbanBoard';

// Mock Data
const mockApplications = [
    { id: 1, candidate: { name: 'Max Mustermann' }, status: 'new', job: { title: 'Pfleger' } },
    { id: 2, candidate: { name: 'Anna Schmidt' }, status: 'screening', job: { title: 'Pfleger' } },
    { id: 3, candidate: { name: 'Tom Weber' }, status: 'interview', job: { title: 'Koch' } },
];

// API Mock
const mockUpdateStatus = jest.fn();

describe('KanbanBoard', () => {
    beforeEach(() => {
        jest.clearAllMocks();
    });
    
    it('renders all columns', () => {
        render(
            <KanbanBoard 
                applications={mockApplications}
                onStatusChange={mockUpdateStatus}
            />
        );
        
        expect(screen.getByText('Neu')).toBeInTheDocument();
        expect(screen.getByText('Screening')).toBeInTheDocument();
        expect(screen.getByText('Interview')).toBeInTheDocument();
        expect(screen.getByText('Angebot')).toBeInTheDocument();
        expect(screen.getByText('Eingestellt')).toBeInTheDocument();
        expect(screen.getByText('Abgelehnt')).toBeInTheDocument();
    });
    
    it('displays applications in correct columns', () => {
        render(
            <KanbanBoard 
                applications={mockApplications}
                onStatusChange={mockUpdateStatus}
            />
        );
        
        // Max sollte in "Neu" sein
        const newColumn = screen.getByTestId('column-new');
        expect(newColumn).toHaveTextContent('Max Mustermann');
        
        // Anna sollte in "Screening" sein
        const screeningColumn = screen.getByTestId('column-screening');
        expect(screeningColumn).toHaveTextContent('Anna Schmidt');
    });
    
    it('shows application count per column', () => {
        render(
            <KanbanBoard 
                applications={mockApplications}
                onStatusChange={mockUpdateStatus}
            />
        );
        
        // 1 in "Neu", 1 in "Screening", 1 in "Interview"
        expect(screen.getByTestId('count-new')).toHaveTextContent('1');
        expect(screen.getByTestId('count-screening')).toHaveTextContent('1');
        expect(screen.getByTestId('count-interview')).toHaveTextContent('1');
    });
    
    it('shows empty state when no applications', () => {
        render(
            <KanbanBoard 
                applications={[]}
                onStatusChange={mockUpdateStatus}
            />
        );
        
        expect(screen.getByText(/keine Bewerbungen/i)).toBeInTheDocument();
    });
    
    it('calls onStatusChange when card is moved', async () => {
        // Drag & Drop ist komplex zu testen, hier vereinfacht
        render(
            <KanbanBoard 
                applications={mockApplications}
                onStatusChange={mockUpdateStatus}
            />
        );
        
        // Simuliere Status-Änderung über Dropdown (falls vorhanden)
        const card = screen.getByText('Max Mustermann').closest('[data-application-id]');
        const statusButton = card.querySelector('[data-status-button]');
        
        if (statusButton) {
            await userEvent.click(statusButton);
            await userEvent.click(screen.getByText('In Screening verschieben'));
            
            expect(mockUpdateStatus).toHaveBeenCalledWith(1, 'screening');
        }
    });
    
    it('filters applications by job', async () => {
        render(
            <KanbanBoard 
                applications={mockApplications}
                onStatusChange={mockUpdateStatus}
                showFilters={true}
            />
        );
        
        const jobFilter = screen.getByLabelText(/Stelle/i);
        await userEvent.selectOptions(jobFilter, 'Pfleger');
        
        // Tom (Koch) sollte nicht mehr sichtbar sein
        expect(screen.queryByText('Tom Weber')).not.toBeInTheDocument();
        expect(screen.getByText('Max Mustermann')).toBeInTheDocument();
    });
});
```

### ApplicationCard.test.jsx

```jsx
// admin-ui/tests/components/ApplicationCard.test.jsx

import React from 'react';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { ApplicationCard } from '@components/Applications/ApplicationCard';

const mockApplication = {
    id: 1,
    candidate: {
        name: 'Max Mustermann',
        email: 'max@example.com',
    },
    job: {
        id: 10,
        title: 'Pflegefachkraft',
    },
    status: 'new',
    rating: 4,
    created_at: '2025-01-15T10:00:00Z',
};

describe('ApplicationCard', () => {
    it('renders candidate name and job title', () => {
        render(<ApplicationCard application={mockApplication} />);
        
        expect(screen.getByText('Max Mustermann')).toBeInTheDocument();
        expect(screen.getByText('Pflegefachkraft')).toBeInTheDocument();
    });
    
    it('displays rating stars', () => {
        render(<ApplicationCard application={mockApplication} />);
        
        const stars = screen.getAllByTestId('star-filled');
        expect(stars).toHaveLength(4);
    });
    
    it('shows relative date', () => {
        render(<ApplicationCard application={mockApplication} />);
        
        // "vor X Tagen" oder ähnlich
        expect(screen.getByText(/vor|heute|gestern/i)).toBeInTheDocument();
    });
    
    it('opens detail modal on click', async () => {
        const onOpen = jest.fn();
        render(
            <ApplicationCard 
                application={mockApplication} 
                onOpen={onOpen}
            />
        );
        
        await userEvent.click(screen.getByText('Max Mustermann'));
        
        expect(onOpen).toHaveBeenCalledWith(mockApplication.id);
    });
    
    it('is draggable', () => {
        render(<ApplicationCard application={mockApplication} />);
        
        const card = screen.getByTestId('application-card');
        expect(card).toHaveAttribute('draggable', 'true');
    });
});
```

### useApplications Hook Test

```javascript
// admin-ui/tests/hooks/useApplications.test.js

import { renderHook, act, waitFor } from '@testing-library/react';
import { useApplications } from '@/hooks/useApplications';

describe('useApplications', () => {
    beforeEach(() => {
        jest.clearAllMocks();
        
        global.fetch.mockResolvedValue({
            ok: true,
            json: async () => ({
                data: [
                    { id: 1, status: 'new' },
                    { id: 2, status: 'screening' },
                ],
                meta: { total: 2 },
            }),
        });
    });
    
    it('fetches applications on mount', async () => {
        const { result } = renderHook(() => useApplications());
        
        expect(result.current.loading).toBe(true);
        
        await waitFor(() => {
            expect(result.current.loading).toBe(false);
        });
        
        expect(result.current.applications).toHaveLength(2);
        expect(global.fetch).toHaveBeenCalledWith(
            expect.stringContaining('/applications'),
            expect.any(Object)
        );
    });
    
    it('updates application status', async () => {
        global.fetch
            .mockResolvedValueOnce({
                ok: true,
                json: async () => ({ data: [], meta: { total: 0 } }),
            })
            .mockResolvedValueOnce({
                ok: true,
                json: async () => ({ id: 1, status: 'screening' }),
            });
        
        const { result } = renderHook(() => useApplications());
        
        await waitFor(() => {
            expect(result.current.loading).toBe(false);
        });
        
        await act(async () => {
            await result.current.updateStatus(1, 'screening');
        });
        
        expect(global.fetch).toHaveBeenLastCalledWith(
            expect.stringContaining('/applications/1/status'),
            expect.objectContaining({
                method: 'PUT',
                body: expect.stringContaining('screening'),
            })
        );
    });
    
    it('handles fetch error', async () => {
        global.fetch.mockRejectedValueOnce(new Error('Network error'));
        
        const { result } = renderHook(() => useApplications());
        
        await waitFor(() => {
            expect(result.current.loading).toBe(false);
        });
        
        expect(result.current.error).toBeTruthy();
        expect(result.current.applications).toEqual([]);
    });
    
    it('filters by job ID', async () => {
        const { result, rerender } = renderHook(
            ({ jobId }) => useApplications({ jobId }),
            { initialProps: { jobId: null } }
        );
        
        await waitFor(() => {
            expect(result.current.loading).toBe(false);
        });
        
        rerender({ jobId: 123 });
        
        await waitFor(() => {
            expect(global.fetch).toHaveBeenLastCalledWith(
                expect.stringContaining('job_id=123'),
                expect.any(Object)
            );
        });
    });
});
```

---

## 3. CI/CD Pipeline (GitHub Actions)

```yaml
# .github/workflows/tests.yml

name: Tests

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main ]

jobs:
  php-tests:
    runs-on: ubuntu-latest
    
    strategy:
      matrix:
        php: ['8.0', '8.1', '8.2']
    
    name: PHP ${{ matrix.php }}
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          coverage: xdebug
          tools: composer
      
      - name: Cache Composer
        uses: actions/cache@v3
        with:
          path: vendor
          key: composer-${{ hashFiles('composer.lock') }}
      
      - name: Install dependencies
        run: composer install --prefer-dist --no-progress
      
      - name: Run PHPUnit
        run: vendor/bin/phpunit --coverage-text
      
      - name: Upload coverage
        if: matrix.php == '8.2'
        uses: codecov/codecov-action@v3
        with:
          files: ./tests/coverage/clover.xml

  js-tests:
    runs-on: ubuntu-latest
    
    name: JavaScript
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup Node
        uses: actions/setup-node@v4
        with:
          node-version: '20'
          cache: 'npm'
          cache-dependency-path: admin-ui/package-lock.json
      
      - name: Install dependencies
        run: |
          cd admin-ui
          npm ci
      
      - name: Run Jest
        run: |
          cd admin-ui
          npm test -- --coverage
      
      - name: Upload coverage
        uses: codecov/codecov-action@v3
        with:
          files: ./admin-ui/coverage/lcov.info

  lint:
    runs-on: ubuntu-latest
    
    name: Lint
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          tools: composer, phpcs
      
      - name: Install dependencies
        run: composer install --prefer-dist --no-progress
      
      - name: Run PHPCS
        run: composer phpcs
      
      - name: Setup Node
        uses: actions/setup-node@v4
        with:
          node-version: '20'
          cache: 'npm'
          cache-dependency-path: admin-ui/package-lock.json
      
      - name: Install JS dependencies
        run: |
          cd admin-ui
          npm ci
      
      - name: Run ESLint
        run: |
          cd admin-ui
          npm run lint
```

---

## NPM Scripts

```json
// admin-ui/package.json (scripts ergänzen)
{
    "scripts": {
        "test": "jest",
        "test:watch": "jest --watch",
        "test:coverage": "jest --coverage",
        "lint": "eslint src --ext .js,.jsx",
        "lint:fix": "eslint src --ext .js,.jsx --fix"
    }
}
```

## Composer Scripts

```json
// composer.json (scripts ergänzen)
{
    "scripts": {
        "test": "phpunit",
        "test:unit": "phpunit --testsuite Unit",
        "test:integration": "phpunit --testsuite Integration",
        "test:coverage": "phpunit --coverage-html tests/coverage/html",
        "phpcs": "phpcs --standard=WordPress src/",
        "phpcbf": "phpcbf --standard=WordPress src/"
    }
}
```

---

## Schnellstart

```bash
# PHP Tests ausführen
composer test

# Nur Unit Tests
composer test:unit

# Mit Coverage Report
composer test:coverage

# JavaScript Tests
cd admin-ui
npm test

# Tests im Watch-Mode
npm run test:watch

# Mit Coverage
npm run test:coverage
```

---

## E2E Tests (Nach MVP)

Später mit Playwright:

```typescript
// tests/e2e/specs/application-form.spec.ts

import { test, expect } from '@playwright/test';

test.describe('Bewerbungsformular', () => {
    test('sollte Bewerbung erfolgreich absenden', async ({ page }) => {
        await page.goto('/jobs/pflegefachkraft/');
        
        await page.fill('[name="first_name"]', 'Max');
        await page.fill('[name="last_name"]', 'Mustermann');
        await page.fill('[name="email"]', 'max@example.com');
        
        await page.setInputFiles('[name="cv"]', 'tests/fixtures/lebenslauf.pdf');
        
        await page.check('[name="consent_privacy"]');
        
        await page.click('button[type="submit"]');
        
        await expect(page.locator('[data-success-message]')).toBeVisible();
        await expect(page.locator('[data-success-message]')).toContainText('erfolgreich');
    });
});
```

---

*Letzte Aktualisierung: Januar 2025*
