// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// at your option any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Scroll-triggered visual report animation for PassionFinder.
 *
 * @module     mod_passionfinder/visual_report
 * @copyright  2026 Johan Venter
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Initialise scroll-triggered report animations.
 *
 * Hearts are animated once, when their visual summary enters the viewport.
 */
export const init = () => {
    const summaries = document.querySelectorAll('.path-mod-passionfinder .passionfinder-visual-summary');

    if (!summaries.length) {
        return;
    }

    if (!('IntersectionObserver' in window)) {
        summaries.forEach((summary) => {
            summary.classList.add('passionfinder-visual-visible');
        });
        return;
    }

    const observer = new IntersectionObserver((entries, activeObserver) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) {
                return;
            }

            entry.target.classList.add('passionfinder-visual-visible');
            activeObserver.unobserve(entry.target);
        });
    }, {
        root: null,
        rootMargin: '0px 0px -12% 0px',
        threshold: 0.2
    });

    summaries.forEach((summary) => {
        observer.observe(summary);
    });
};
