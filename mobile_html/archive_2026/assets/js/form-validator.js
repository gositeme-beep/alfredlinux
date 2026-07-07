/**
 * GoSiteMe Form Validator v1.0
 * ────────────────────────────
 * Client-side form validation with real-time feedback.
 * Zero dependencies. Works with any <form> element.
 *
 * Usage:
 *   <form data-validate>
 *     <input name="email" required data-validate="email" data-label="Email">
 *     <input name="phone" data-validate="phone" data-label="Phone">
 *     <input name="password" required data-validate="password" data-label="Password" minlength="8">
 *     <button type="submit">Submit</button>
 *   </form>
 *
 *   // Or programmatic:
 *   const fv = new FormValidator(formElement, { onSubmit: (data) => { ... } });
 *
 * @since v14.0
 */
'use strict';

class FormValidator {
  static RULES = {
    email:    { pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/, message: 'Enter a valid email address' },
    phone:    { pattern: /^[\d\s\-+().]{7,20}$/, message: 'Enter a valid phone number' },
    url:      { pattern: /^https?:\/\/.+\..+/, message: 'Enter a valid URL starting with http(s)://' },
    alpha:    { pattern: /^[a-zA-Z\s]+$/, message: 'Only letters and spaces allowed' },
    numeric:  { pattern: /^\d+$/, message: 'Only numbers allowed' },
    alphanumeric: { pattern: /^[a-zA-Z0-9\s]+$/, message: 'Only letters, numbers, and spaces allowed' },
    password: {
      validate: (v) => {
        const issues = [];
        if (v.length < 8)           issues.push('at least 8 characters');
        if (!/[A-Z]/.test(v))       issues.push('an uppercase letter');
        if (!/[a-z]/.test(v))       issues.push('a lowercase letter');
        if (!/\d/.test(v))          issues.push('a number');
        if (!/[!@#$%^&*]/.test(v))  issues.push('a special character (!@#$%^&*)');
        return issues.length ? `Password needs ${issues.join(', ')}` : null;
      }
    },
    match: {
      validate: (v, el) => {
        const target = el.getAttribute('data-match');
        if (!target) return null;
        const other = el.form?.querySelector(`[name="${target}"]`);
        return other && v !== other.value ? 'Fields do not match' : null;
      }
    }
  };

  constructor(form, opts = {}) {
    this.form = form;
    this.opts = {
      liveValidation: opts.liveValidation ?? true,
      errorClass:     opts.errorClass ?? 'fv-error',
      successClass:   opts.successClass ?? 'fv-valid',
      messageClass:   opts.messageClass ?? 'fv-message',
      onSubmit:       opts.onSubmit ?? null,
      onError:        opts.onError ?? null,
    };
    this._bound = new Map();
    this._init();
  }

  _init() {
    this.form.setAttribute('novalidate', '');
    this.form.addEventListener('submit', (e) => this._handleSubmit(e));

    if (this.opts.liveValidation) {
      this._getFields().forEach(field => {
        const handler = () => this._validateField(field);
        field.addEventListener('blur', handler);
        field.addEventListener('input', handler);
        this._bound.set(field, handler);
      });
    }
  }

  _getFields() {
    return [...this.form.querySelectorAll('input, select, textarea')].filter(
      el => el.name && el.type !== 'hidden' && el.type !== 'submit' && el.type !== 'button'
    );
  }

  _validateField(field) {
    const value = field.value.trim();
    const label = field.getAttribute('data-label') || field.name;
    let error = null;

    // Required check
    if (field.required && !value) {
      error = `${label} is required`;
    }

    // Min/max length
    if (!error && value) {
      const min = parseInt(field.getAttribute('minlength'));
      const max = parseInt(field.getAttribute('maxlength'));
      if (min && value.length < min) error = `${label} must be at least ${min} characters`;
      if (max && value.length > max) error = `${label} must be at most ${max} characters`;
    }

    // Pattern rule
    if (!error && value) {
      const rules = (field.getAttribute('data-validate') || '').split(',').map(r => r.trim()).filter(Boolean);
      for (const ruleName of rules) {
        const rule = FormValidator.RULES[ruleName];
        if (!rule) continue;
        if (rule.validate) {
          error = rule.validate(value, field);
        } else if (rule.pattern && !rule.pattern.test(value)) {
          error = rule.message;
        }
        if (error) break;
      }
    }

    // Custom HTML5 pattern
    if (!error && value && field.pattern) {
      const re = new RegExp(`^${field.pattern}$`);
      if (!re.test(value)) {
        error = field.title || `${label} doesn't match the required format`;
      }
    }

    this._showFeedback(field, error);
    return error;
  }

  _showFeedback(field, error) {
    const { errorClass, successClass, messageClass } = this.opts;
    const existing = field.parentElement?.querySelector(`.${messageClass}`);
    if (existing) existing.remove();

    field.classList.remove(errorClass, successClass);

    if (error) {
      field.classList.add(errorClass);
      const msg = document.createElement('div');
      msg.className = messageClass;
      msg.textContent = error;
      msg.setAttribute('role', 'alert');
      field.parentElement?.appendChild(msg);
    } else if (field.value.trim()) {
      field.classList.add(successClass);
    }
  }

  _handleSubmit(e) {
    e.preventDefault();
    const fields = this._getFields();
    const errors = [];

    fields.forEach(field => {
      const err = this._validateField(field);
      if (err) errors.push({ field: field.name, message: err });
    });

    if (errors.length) {
      // Focus first error field
      const firstBad = this.form.querySelector(`.${this.opts.errorClass}`);
      if (firstBad) firstBad.focus();
      this.opts.onError?.(errors);
      return;
    }

    // Build form data
    const formData = new FormData(this.form);
    const data = Object.fromEntries(formData.entries());

    if (this.opts.onSubmit) {
      this.opts.onSubmit(data, this.form);
    } else {
      this.form.submit();
    }
  }

  reset() {
    this._getFields().forEach(field => this._showFeedback(field, null));
  }

  destroy() {
    this._bound.forEach((handler, field) => {
      field.removeEventListener('blur', handler);
      field.removeEventListener('input', handler);
    });
    this._bound.clear();
    this.reset();
  }
}

// Auto-init forms with data-validate attribute
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('form[data-validate]').forEach(form => {
    form._validator = new FormValidator(form);
  });
});

// Export for ES module or global
if (typeof window !== 'undefined') window.FormValidator = FormValidator;
