/**
 * @file
 * Deploy Steps behaviors.
 */

/**
 * @param {Object} Drupal  The Drupal object.
 */
((Drupal) => {
  Drupal.behaviors.yourExtension = {
    formatLoadTime(date) {
      return `Page loaded: ${date.toLocaleString()}`;
    },
    attach(context) {
      const elements = context.querySelectorAll(
        '[data-deploy_steps-time]:not(.deploy_steps-processed)',
      );

      elements.forEach((element) => {
        element.classList.add('deploy_steps-processed');
        element.textContent = this.formatLoadTime(new Date());
      });
    },
  };
})(Drupal);
