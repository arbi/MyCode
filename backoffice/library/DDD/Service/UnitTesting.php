<?php
namespace DDD\Service;

class UnitTesting extends ServiceBase
{
    CONST LOG_BO_FILE_PATH  = '/ginosi/backoffice/module/UnitTesting/test/logfile.json';
    CONST LOG_WEB_FILE_PATH = '/ginosi/website/module/UnitTesting/test/logfile.json';
    CONST LOG_API_FILE_PATH = '/ginosi/api/module/UnitTesting/test/logfile.json';

    CONST PASSED_VALUE  = 'pass';
    CONST FAILED_VALUE  = 'fail';
    CONST ERROR_VALUE   = 'error';
    CONST WARNING_VALUE = 'warning';

    /**
     * @return array
     */
    public static function getStatusesForSelect()
    {
        return [
            '' => '-- All Statuses --',
            self::PASSED_VALUE  => 'Passed',
            self::FAILED_VALUE  => 'Failed',
            self::ERROR_VALUE   => 'Have Error',
            self::WARNING_VALUE => 'Warning',
        ];
    }

    /**
     * @return bool
     */
    protected function logExists()
    {
        return (file_exists(self::LOG_BO_FILE_PATH) && file_exists(self::LOG_WEB_FILE_PATH) && file_exists(self::LOG_API_FILE_PATH));
    }

    /**
     * @return array
     */
    protected function transferLogFileNotValidJsonToValid()
    {
        if (!$this->logExists()) {
            return [
              'status' => 'error',
              'msg'    => '<div>Log File Does Not Exist</div><br><div class="text-size-14">For generation do the following please:' .
                  '<ul>
                        <li>SSH to alpha server</li>
                        <li>cd /ginosi/backoffice/module/UnitTesting/test</li>
                        <li>Run "phpunit"</li>
                  </ul></div>'
            ];
        }

        $logArray = [];
        $logFiles = [
            self::LOG_BO_FILE_PATH,
            self::LOG_WEB_FILE_PATH,
            self::LOG_API_FILE_PATH,
        ];

        foreach ($logFiles as $file) {
            $contents = file_get_contents($file);
            $contents = str_replace('}{', '},{', $contents);
            $contents = '[' . $contents . ']';

            $logArray = array_merge($logArray, json_decode($contents, true));
        }

        if ($logArray == null) {
            return [
                'status' => 'error',
                'msg'    => 'Log file was modified into not valid JSON'
            ];
        }

        return [
            'status' => 'success',
            'logArray' => $logArray
        ];
    }

    /**
     * @return array
     */
    public function getAllCategories()
    {
        $logFileJsonToArrayResult = $this->transferLogFileNotValidJsonToValid();
        if ($logFileJsonToArrayResult['status'] == 'error') {
            return $logFileJsonToArrayResult;
        }

        $logArray            = $logFileJsonToArrayResult['logArray'];
        $categoriesArray[''] = '-- All Categories--';

        foreach ($logArray as $row) {
            if (!isset($row['event'])) {
                //just for security, it should be set
                continue;
            }

            if ($row['event'] == 'suiteStart') {
                if (!isset($row['suite'])
                    || $row['suite'] == ""
                    || strpos($row['suite'], 'UnitTestingTest') !== false
                ) {
                    /**
                     * examples:
                     *
                     * {
                     * "event": "suiteStart",
                     * "suite": "",
                     * "tests": 9
                     * }
                     *
                     * OR
                     *
                     * {
                     * "event": "suiteStart",
                     * "suite": "UnitTestingTest\\Controller\\NewsControllerTest",
                     * "tests": 6
                     * }
                     *
                     */
                    continue;
                }

                if (strpos($row['suite'], '\\') === false) {
                    $categoriesArray[$row['suite']] = $row['suite'];
                }
            }
        }

        return count($categoriesArray)
            ? ['status' => 'success', 'categories' => $categoriesArray]
            : ['status' => 'error', 'msg' => 'No Specified Categories Were Found'];
    }

    /**
     * @param array $categories
     * @param array $statuses
     * @param string $testName
     * @return array
     */
    public function getAllTests($categories, $statuses, $testName)
    {
        $logFileJsonToArrayResult = $this->transferLogFileNotValidJsonToValid();
        if ($logFileJsonToArrayResult['status'] == 'error') {
            return $logFileJsonToArrayResult;
        }

        $logArray = $logFileJsonToArrayResult['logArray'];

        $resultCategorizedArray = [];
        $lastCategory = false;
        $totalArray =
            [
                'totalCount'   => 0,
                'failCount'    => 0,
                'errorCount'   => 0,
                'passCount'    => 0,
                'warningCount' => 0,
            ];

        foreach ($logArray as $row) {
            if (!isset($row['event'])) {
                //just for security, it should be set
                continue;
            }

            if ($row['event'] == 'suiteStart' && strpos($row['suite'], '\\') === false) {
                if (!isset($row['suite'])
                    || $row['suite'] == ""
                    || strpos($row['suite'], 'UnitTestingTest') !== false
                ) {
                    /**
                     * examples:
                     *
                     * {
                     * "event": "suiteStart",
                     * "suite": "",
                     * "tests": 9
                     * }
                     *
                     * OR
                     *
                     * {
                     * "event": "suiteStart",
                     * "suite": "UnitTestingTest\\Controller\\NewsControllerTest",
                     * "tests": 6
                     * }
                     *
                     */
                    continue;
                }

                if (!empty($categories)) {
                    if (!in_array($row['suite'], $categories)) {
                        $lastCategory = false;
                        continue;
                    }
                }
                $lastCategory = $row['suite'];
                $resultCategorizedArray[$lastCategory] =
                    [
                        'tests'        => [],
                        'totalCount'   => 0,
                        'failCount'    => 0,
                        'errorCount'   => 0,
                        'passCount'    => 0,
                        'warningCount' => 0,
                    ];
            } elseif ($row['event'] == 'test') {
                // special logic for warning (e.g. No tests)
                if ($row['status'] == 'fail' && $row['test'] == 'Warning') {
                    $row['status'] = 'warning';
                }

                if ($lastCategory === false
                    || empty($resultCategorizedArray[$lastCategory])
                ) {
                    continue;
                }

                if (!empty($statuses)) {
                    if (!in_array($row['status'], $statuses)) {
                        continue;
                    }
                }

                if ($testName != '') {
                    if (strpos($row['test'], $testName) === false) {
                        continue;
                    }
                }

                if ((!empty($categories) && !in_array($lastCategory, $categories)) || !$lastCategory) {
                    continue;
                }

                array_push($resultCategorizedArray[$lastCategory]['tests'],
                    [
                        'status'  => $row['status'],
                        'time'    => $row['time'],
                        'trace'   => $row['trace'],
                        'test'    => $row['test'],
                        'message' => isset($row['message']) ? $row['message'] : false
                    ]
                );

                $resultCategorizedArray[$lastCategory]['totalCount']++;
                $resultCategorizedArray[$lastCategory][$row['status'] . 'Count']++;
                $totalArray[$row['status'] . 'Count']++;
                $totalArray['totalCount']++;
            }
        }

        $sort = array();
        foreach($resultCategorizedArray as $k=>$v) {
            $sort['failCount'][$k]    = $v['failCount'];
            $sort['errorCount'][$k]   = $v['errorCount'];
            $sort['warningCount'][$k] = $v['warningCount'];
        }

        array_multisort($sort['failCount'], SORT_DESC, $sort['errorCount'], SORT_DESC, $resultCategorizedArray);

        return [
            'status' => 'success',
            'resultCategorizedArray' => $resultCategorizedArray,
            'totalArray'  => $totalArray
        ];
    }
}
