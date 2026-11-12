// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * mod/hotpot/attempt/hp/7/jmatch/jmatch.js
 *
 * HP7 JMatch uses a JSON object to hold the left and right items:
 *   - JsonEx for the standard (select element) version
 *   - DragEx for the drag-and-drop version
 * Each left item has a Group, and each right item has a list of Groups.
 * A right item is a correct match for a left item if its Groups include the left item's Group.
 *
 * @package   mod-hotpot
 * @copyright 2020 Gordon Bateson <gordon.bateson@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * JMatch
 *
 * @param integer sendallclicks
 * @param integer ajax
 */
function JMatch(sendallclicks, ajax) {
    this.quiztype = 'JMatch';

    /**
     * getEx
     *
     * @return object the HP7 JSON object (JsonEx or DragEx), or null
     */
    this.getEx = function () {
        return window.JsonEx || window.DragEx || null;
    };

    /**
     * initQuestion
     *
     * @param integer i
     */
    this.initQuestion = function (i) {
        var LI = this.getEx().LeftItems[i];
        this.questions[i].name = GetTextFromNode(document.getElementById('L_' + LI.OrigPos));
        this.questions[i].type = 4;  // 4 = JMatch
        this.questions[i].text = ''; // always empty for JMatch
    };

    /**
     * getGuess
     *
     * @param object LI a left item
     * @return object the right item matched with this left item, or null
     */
    this.getGuess = function (LI) {
        var ex = this.getEx();
        if (LI.Select) {
            // standard version (JsonEx): the select element in this row
            var value = LI.Select.options[LI.Select.selectedIndex].value;
            if (value == -1) {
                return null;
            }
            return ex.GetRightItemByOrigPos(value);
        }
        // drag-and-drop version (DragEx): the right item, if any, dropped on this left item
        for (var i=0; i<ex.RightItems.length; i++) {
            if (ex.RightItems[i].MatchedWith == LI.OrigPos) {
                return ex.RightItems[i];
            }
        }
        return null;
    };

    /**
     * getText
     *
     * @param object RI a right item
     * @return string
     */
    this.getText = function (RI) {
        return GetTextFromNode(RI.Card || document.querySelector('option[data-origPos="R_' + RI.OrigPos + '"]'));
    };

    /**
     * getScore
     *
     * Calculate the score in the same way as JsonEx.CheckAnswers() and DragEx.CheckAnswers()
     *
     * @return integer
     */
    this.getScore = function () {
        var ex = this.getEx();
        var count = 0;
        var correct = 0;
        var i, LI, RI;
        if (window.JsonEx) {
            for (i=0; i<ex.LeftItems.length; i++) {
                LI = ex.LeftItems[i];
                RI = this.getGuess(LI);
                count++;
                if (LI.IsDistractor) {
                    if (RI==null) {
                        correct++;
                    }
                } else if (RI && RI.Groups.indexOf(LI.Group) >= 0) {
                    correct++;
                }
            }
        } else {
            for (i=0; i<ex.RightItems.length; i++) {
                RI = ex.RightItems[i];
                if (ex.GetLeftItemByOrigPos(RI.OrigPos)) {
                    count++;
                    if (RI.MatchedWith != -1) {
                        LI = ex.GetLeftItemByOrigPos(RI.MatchedWith);
                        if (LI && RI.Groups.indexOf(LI.Group) >= 0) {
                            correct++;
                        }
                    }
                } else if (RI.MatchedWith != -1) {
                    correct--; // distractor that has been matched
                }
            }
        }
        if (count==0) {
            return 0;
        }
        var score = Math.round((100 * (correct - (ex.Penalties || 0))) / count);
        return Math.max(0, score);
    };

    /**
     * onclickCheck
     *
     * @param boolean setScores
     */
    this.onclickCheck = function (setScores) {
        var ex = this.getEx();
        if (ex==null) {
            return;
        }
        for (var i=0; i<ex.LeftItems.length; i++) {
            if (this.questions[i]==null) {
                continue; // shouldn't happen !!
            }
            if (this.questions[i].correct.length) {
                // this question has already been correctly answered
                continue;
            }

            var LI = ex.LeftItems[i];
            var RI = this.getGuess(LI);
            if (RI==null) {
                // no guess for this left item
                continue;
            }

            var g = this.getText(RI);
            if (RI.Groups.indexOf(LI.Group) >= 0) {
                var responses = this.questions[i].correct;
            } else {
                var responses = this.questions[i].wrong;
            }

            var r_max = responses.length;
            for (var r=0; r<r_max; r++) {
                if (g==responses[r]) {
                    // this g(uess) is already in the array of responses
                    break;
                }
            }

            if (r==r_max) {
                // this is new response to questions[i]
                responses[r] = g;
                this.questions[i].checks++;
            }
        }

        // HP7 keeps the penalties in the JSON object
        window.Penalties = (ex.Penalties || 0);

        if (setScores) {
            window.Score = this.getScore();
        }
    };

    var ex = this.getEx();
    this.init((ex ? ex.LeftItems.length : 0), sendallclicks, ajax);
}
JMatch.prototype = new hpQuizAttempt();
