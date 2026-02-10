<?php
/**
 * Elementor Stubs für Unit Tests
 *
 * Minimale Stubs für Elementor-Klassen die in den Widget-Tests benötigt werden.
 *
 * @package RecruitingPlaybook\Tests
 */

declare(strict_types=1);

namespace Elementor;

// Controls_Manager Stub.
if ( ! class_exists( '\Elementor\Controls_Manager' ) ) {
	class Controls_Manager {
		const TAB_CONTENT = 'content';
		const TAB_STYLE   = 'style';
		const TEXT         = 'text';
		const TEXTAREA     = 'textarea';
		const NUMBER       = 'number';
		const SELECT       = 'select';
		const SWITCHER     = 'switcher';
	}
}

// Elements_Manager Stub.
if ( ! class_exists( '\Elementor\Elements_Manager' ) ) {
	class Elements_Manager {
		private array $categories = [];

		public function add_category( string $id, array $args ): void {
			$this->categories[ $id ] = $args;
		}

		public function get_categories(): array {
			return $this->categories;
		}
	}
}

// Widgets_Manager Stub.
if ( ! class_exists( '\Elementor\Widgets_Manager' ) ) {
	class Widgets_Manager {
		private array $widgets = [];

		public function register( Widget_Base $widget ): void {
			$this->widgets[ $widget->get_name() ] = $widget;
		}

		public function get_registered(): array {
			return $this->widgets;
		}
	}
}

// Widget_Base Stub.
if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
	abstract class Widget_Base {
		private array $settings = [];
		private array $controls = [];
		private array $sections = [];

		abstract public function get_name(): string;

		public function get_title(): string {
			return '';
		}

		public function get_icon(): string {
			return 'eicon-cog';
		}

		public function get_categories(): array {
			return [ 'general' ];
		}

		public function get_keywords(): array {
			return [];
		}

		public function get_style_depends(): array {
			return [];
		}

		public function get_settings_for_display(): array {
			return $this->settings;
		}

		/**
		 * Für Tests: Settings setzen.
		 */
		public function set_settings( array $settings ): void {
			$this->settings = $settings;
		}

		protected function start_controls_section( string $id, array $args = [] ): void {
			$this->sections[ $id ] = $args;
		}

		protected function end_controls_section(): void {}

		protected function add_control( string $id, array $args = [] ): void {
			$this->controls[ $id ] = $args;
		}

		/**
		 * Für Tests: Registrierte Controls abrufen.
		 */
		public function get_controls_for_test(): array {
			return $this->controls;
		}

		/**
		 * Für Tests: Registrierte Sections abrufen.
		 */
		public function get_sections_for_test(): array {
			return $this->sections;
		}

		protected function register_controls(): void {}

		protected function render(): void {}

		protected function content_template(): void {}
	}
}
