<?php
declare(ticks=1);
namespace libs\utils;

/**
 * MultiProcess 多进程处理工具类
 * 
 * @package default
 */
class MultiProcess
{/*{{{*/
    /**
     * ppid 父进程pid
     * 
     * @var float
     * @access private
     */
    private $ppid = 0;

    /**
     * step 记录已经启过多少个子进程了
     * 
     * @var float
     * @access private
     */
    private $step = 0;

    /**
     * childRen 记录父进程下的子进程pid
     * 
     * @var array
     * @access private
     */
    private $childRen = array();

    /**
     * processCnt 并发运行进程数
     * 
     * @var mixed
     * @access private
     */
    private $processCnt; 

    /**
     * totalStep 总共进程多少步. 每少会走一个进程去处理
     * 
     * @var mixed
     * @access private
     */
    private $totalStep;

    /**
     * stepIds 每一步需要处理的方案id  
     * 
     * @var mixed
     * @access private
     */
    private $stepIds;


    public function __construct($processCnt, $totalStep, DoChild $doChild, $stepIds = array())
    {/*{{{*/
        $this->processCnt = $processCnt;
        $this->totalStep = $totalStep;
        $this->child = $doChild;
        $this->stepIds = $stepIds;
    }/*}}}*/

    /**
     * startChild 启动子进程
     * 
     * @access private
     * @return void
     */
    private function startChild()
    {/*{{{*/
        $_pid = getmypid();
        $pid = pcntl_fork();
        if($pid == 0) {
            $this->ppid = $_pid;
            $this->child->run($this->step, $this->stepIds);
            exit;
        }

        $this->childRen[$pid] = $this->step;
        $this->step ++;
        //$nowProcessCnt = count($this->childRen);
        //printf("现在有进程数{$nowProcessCnt}\n");
        //printf("创建进程{$pid}, step:{$this->step}\n"); 
    }/*}}}*/

    /**
     * initProcesses 初始化, 这时会同时启动processCnt个子进程
     * 
     * @access private
     * @return void
     */
    private function initProcesses()
    {/*{{{*/
        while($this->step < $this->processCnt) {
            $this->startChild();
        }
    }/*}}}*/

    public function run()
    {/*{{{*/
        $failedSteps = array();
        //$this->initProcesses();

        while(true) {
            $exited = pcntl_wait($status, WNOHANG);

            if($exited && pcntl_wexitstatus($status)) {
                $failedSteps[] = $this->childRen[$exited];
            }

            $nowProcessCnt = count($this->childRen);
            if($exited || $nowProcessCnt < $this->processCnt) {
                printf("进程{$exited}退出\n"); 
                printf("现在有进程数:{$nowProcessCnt}\n");
                
                if($exited) {
                    unset($this->childRen[$exited]);
                }

                if($this->canStartChild()) {
                    $this->startChild();
                }
            }

            if(false == $this->canStartChild() && empty($this->childRen)) {
                printf("哈哈结束了\n"); 
                break;
            }

            usleep(50000);

        }

        return $failedSteps;
    }/*}}}*/

    private function canStartChild()
    {/*{{{*/
        return $this->step < $this->totalStep; 
    }/*}}}*/

    private function getStep()
    {/*{{{*/
        return $this->step;
    }/*}}}*/
}/*}}}*/

interface DoChild
{/*{{{*/
    /**
     * run 子进程工作
     * 
     * @param mixed $step  第几步, 从0开始的
     * @param mixed $stepIds  第几步, 这一步需要处理的ids
     * @access public
     */
    public function run($step, $stepIds);
}/*}}}*/
