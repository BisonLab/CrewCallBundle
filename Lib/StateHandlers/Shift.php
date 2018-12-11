<?php

namespace CrewCallBundle\Lib\StateHandlers;

/*
 */
class Shift
{
    private $em;
    private $sm;

    public function __construct($em, $sm)
    {
        $this->em = $em;
        $this->sm = $sm;
    }

    public function handle($shift, $from, $to)
    {
        // Sigh, not working. This way to handle states is too limiting.
        if ($to == "COMPLETED") {
            foreach ($shift->getJobs() as $job) {
                $job->setState("COMPLETED");
                $this->em->persist($job);
                $meta = $this->em->getClassMetadata(get_class($job));
                $uow = $this->em->getUnitOfWork();
                $uow->recomputeSingleEntityChangeSet($meta, $job);
            }
        }
    }
}
