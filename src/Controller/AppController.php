<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */

namespace App\Controller;

use App\Model\Entity\Year;
use App\View\PdfView;
use Cake\Controller\Controller;
use Cake\Event\EventInterface;
use Cake\View\JsonView;

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @link https://book.cakephp.org/4/en/controllers.html#the-app-controller
 * @property \App\Controller\Component\CacheComponent $Cache
 * @property \App\Controller\Component\RoundGetComponent $RoundGet
 * @property \App\Controller\Component\YearGetComponent $YearGet
 */
class AppController extends Controller
{
    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading components.
     *
     * @return void
     * @throws \Exception
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Cache');
        $this->loadComponent('Calc');
        $this->loadComponent('GroupGet');
        $this->loadComponent('MatchGet');
        $this->loadComponent('PlayOff');
        $this->loadComponent('ScrRanking');
        $this->loadComponent('RoundGet');
        $this->loadComponent('Security');
        $this->loadComponent('YearGet');
    }

    public function viewClasses(): array
    {
        return [JsonView::class, PdfView::class];
    }

    public function apiReturn(mixed $object, int $year_id = 0, int $day_id = 0): \Cake\Http\Response
    {
        if ($object) {
            $year = $this->Cache->getCurrentYear()->toArray();
            $year['settings'] = $this->Cache->getSettings();

            // todo: delete after V3.0 complete rollout:
            $year['isTest'] = $year['settings']['isTest'];
            $year['isCurrent'] = $year['settings']['currentYear_id'] == $year['id'] ? 1 : 0;
            $year['currentDay_id'] = $year['settings']['currentDay_id'];
            $year['alwaysAutoUpdateResults'] = $year['settings']['alwaysAutoUpdateResults'];
            // todo end

            $year['day'] = $year['day' . $year['settings']['currentDay_id']];

            $object_array = compact('object');
            $return = array_merge(array('status' => 'success'), array('year' => $year), $object_array);

            // only for archive
            if (($year_id && $year_id != $year['id']) || ($day_id && $day_id != $year['settings']['currentDay_id'])) {
                $return = array_merge($return, array('yearSelected' => $this->YearGet->getYear($year_id, $day_id)));
            }

            if (is_array($return['object']) && ($return['object']['currentRoundId'] ?? 0) > 0) {
                $return['object']['secondsUntilReload'] = $this->RoundGet->getSecondsUntilReload($return['object']['currentRoundId'], $year['settings']);
            }

            $this->set($return);
        } else {
            $this->set(array());
        }

        $this->viewBuilder()->setClassName('Json');

        return $this->response;
    }

    public function pdfReturn(): \Cake\Http\Response
    {
        $this->viewBuilder()->setClassName('Pdf');

        return $this->response;
    }

    public function beforeRender(EventInterface $event): void
    {
        $this->viewBuilder()->setOption('serialize', true);
    }

    protected function getTeamsCountPerGroup(Year $year): int
    {
        return $year->teamsCount > 24 ? 16 : $year->teamsCount;
    }
}
