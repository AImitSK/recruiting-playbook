/**
 * Conditional Logic Handler für Alpine.js
 *
 * Verwaltet dynamische Feldanzeige basierend auf Bedingungen.
 *
 * @package RecruitingPlaybook
 */

/**
 * Conditional Logic Evaluator
 *
 * Evaluiert Bedingungen client-seitig für reaktive Formularfelder.
 */
const ConditionalLogic = {
	/**
	 * Bedingung evaluieren
	 *
	 * @param {Object} condition - Bedingungskonfiguration
	 * @param {string} condition.field - Feldschlüssel
	 * @param {string} condition.operator - Operator
	 * @param {string} condition.value - Erwarteter Wert
	 * @param {Object} formData - Aktueller Formulardatenstand
	 * @returns {boolean} True wenn Bedingung erfüllt
	 */
	evaluate(condition, formData) {
		if (!condition || !condition.field) {
			return true;
		}

		const { field, operator = 'equals', value = '' } = condition;
		const actual = formData[field];

		return this.evaluateOperator(actual, operator, value);
	},

	/**
	 * Mehrere Bedingungen evaluieren
	 *
	 * @param {Array} conditions - Array von Bedingungen
	 * @param {string} logic - 'and' oder 'or'
	 * @param {Object} formData - Formulardaten
	 * @returns {boolean}
	 */
	evaluateMultiple(conditions, logic = 'and', formData) {
		if (!conditions || conditions.length === 0) {
			return true;
		}

		const results = conditions.map(c => this.evaluate(c, formData));

		if (logic === 'or') {
			return results.some(r => r === true);
		}

		return results.every(r => r === true);
	},

	/**
	 * Operator-basierte Auswertung
	 *
	 * @param {*} actual - Tatsächlicher Wert
	 * @param {string} operator - Operator
	 * @param {*} expected - Erwarteter Wert
	 * @returns {boolean}
	 */
	evaluateOperator(actual, operator, expected) {
		const actualStr = this.toString(actual);
		const expectedStr = String(expected || '');

		switch (operator) {
			case 'equals':
				return actualStr === expectedStr;

			case 'not_equals':
				return actualStr !== expectedStr;

			case 'contains':
				return actualStr.includes(expectedStr);

			case 'not_contains':
				return !actualStr.includes(expectedStr);

			case 'starts_with':
				return actualStr.startsWith(expectedStr);

			case 'ends_with':
				return actualStr.endsWith(expectedStr);

			case 'not_empty':
				return this.isNotEmpty(actual);

			case 'empty':
				return this.isEmpty(actual);

			case 'checked':
				return this.isChecked(actual);

			case 'not_checked':
				return !this.isChecked(actual);

			case 'greater_than':
				return this.toNumber(actual) > this.toNumber(expected);

			case 'less_than':
				return this.toNumber(actual) < this.toNumber(expected);

			case 'greater_or_equal':
				return this.toNumber(actual) >= this.toNumber(expected);

			case 'less_or_equal':
				return this.toNumber(actual) <= this.toNumber(expected);

			case 'in':
				const inValues = expectedStr.split(',').map(v => v.trim());
				return inValues.includes(actualStr);

			case 'not_in':
				const notInValues = expectedStr.split(',').map(v => v.trim());
				return !notInValues.includes(actualStr);

			case 'before':
				return this.compareDates(actual, expected, '<');

			case 'after':
				return this.compareDates(actual, expected, '>');

			default:
				return true;
		}
	},

	/**
	 * Wert zu String konvertieren
	 *
	 * @param {*} value
	 * @returns {string}
	 */
	toString(value) {
		if (value === null || value === undefined) {
			return '';
		}
		if (Array.isArray(value)) {
			return value.join(',');
		}
		return String(value);
	},

	/**
	 * Wert zu Nummer konvertieren
	 *
	 * @param {*} value
	 * @returns {number}
	 */
	toNumber(value) {
		const num = parseFloat(value);
		return isNaN(num) ? 0 : num;
	},

	/**
	 * Prüfen ob Wert leer ist
	 *
	 * @param {*} value
	 * @returns {boolean}
	 */
	isEmpty(value) {
		if (value === null || value === undefined || value === '') {
			return true;
		}
		if (Array.isArray(value)) {
			return value.filter(v => v !== '' && v !== null).length === 0;
		}
		return false;
	},

	/**
	 * Prüfen ob Wert nicht leer ist
	 *
	 * @param {*} value
	 * @returns {boolean}
	 */
	isNotEmpty(value) {
		return !this.isEmpty(value);
	},

	/**
	 * Prüfen ob Checkbox aktiviert ist
	 *
	 * @param {*} value
	 * @returns {boolean}
	 */
	isChecked(value) {
		if (typeof value === 'boolean') {
			return value;
		}
		return value && value !== '0' && value !== 'false' && value !== false;
	},

	/**
	 * Datumsvergleich
	 *
	 * @param {string} date1
	 * @param {string} date2
	 * @param {string} operator - '<' oder '>'
	 * @returns {boolean}
	 */
	compareDates(date1, date2, operator) {
		const d1 = new Date(date1);
		const d2 = new Date(date2);

		if (isNaN(d1.getTime()) || isNaN(d2.getTime())) {
			return false;
		}

		if (operator === '<') {
			return d1 < d2;
		}
		return d1 > d2;
	}
};

/**
 * Alpine.js Store für Conditional Logic
 *
 * Verwaltet den Zustand der Feldvisibilität.
 */
document.addEventListener('alpine:init', () => {
	Alpine.store('conditionalLogic', {
		// Feld-Sichtbarkeiten.
		visibility: {},

		// Conditional-Konfigurationen pro Feld.
		conditions: {},

		/**
		 * Bedingungen für ein Feld registrieren
		 *
		 * @param {string} fieldKey - Feldschlüssel
		 * @param {Object} condition - Bedingungskonfiguration
		 */
		register(fieldKey, condition) {
			this.conditions[fieldKey] = condition;
			this.visibility[fieldKey] = true;
		},

		/**
		 * Alle Sichtbarkeiten aktualisieren
		 *
		 * @param {Object} formData - Aktuelle Formulardaten
		 */
		update(formData) {
			for (const [fieldKey, condition] of Object.entries(this.conditions)) {
				this.visibility[fieldKey] = ConditionalLogic.evaluate(condition, formData);
			}
		},

		/**
		 * Prüfen ob Feld sichtbar ist
		 *
		 * @param {string} fieldKey
		 * @returns {boolean}
		 */
		isVisible(fieldKey) {
			return this.visibility[fieldKey] !== false;
		}
	});

	/**
	 * Alpine.js Directive: x-conditional
	 *
	 * Beispiel: <div x-conditional="{ field: 'show_other', operator: 'checked' }">
	 */
	Alpine.directive('conditional', (el, { expression }, { evaluate, effect }) => {
		const condition = evaluate(expression);

		if (!condition || !condition.field) {
			return;
		}

		effect(() => {
			const formData = Alpine.store('formData') || {};
			const visible = ConditionalLogic.evaluate(condition, formData);

			if (visible) {
				el.removeAttribute('hidden');
				el.style.display = '';
			} else {
				el.setAttribute('hidden', '');
				el.style.display = 'none';
			}
		});
	});
});

/**
 * Alpine.js Magic: $conditional
 *
 * Ermöglicht einfache Conditional-Prüfungen in Templates.
 * Beispiel: <div x-show="$conditional({ field: 'type', operator: 'equals', value: 'other' })">
 */
document.addEventListener('alpine:init', () => {
	Alpine.magic('conditional', (el) => {
		return (condition) => {
			const component = Alpine.$data(el);
			const formData = component.formData || {};
			return ConditionalLogic.evaluate(condition, formData);
		};
	});

	/**
	 * Alpine.js Magic: $conditionalMultiple
	 *
	 * Für mehrere Bedingungen.
	 * Beispiel: <div x-show="$conditionalMultiple([...], 'and')">
	 */
	Alpine.magic('conditionalMultiple', (el) => {
		return (conditions, logic = 'and') => {
			const component = Alpine.$data(el);
			const formData = component.formData || {};
			return ConditionalLogic.evaluateMultiple(conditions, logic, formData);
		};
	});
});

// Für externe Nutzung exportieren.
window.RPConditionalLogic = ConditionalLogic;
